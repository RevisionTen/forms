<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Controller;

use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\CommandBus;
use RevisionTen\CQRS\Services\MessageBus;
use RevisionTen\Forms\Command\FormAddItemCommand;
use RevisionTen\Forms\Command\FormCloneCommand;
use RevisionTen\Forms\Command\FormCreateCommand;
use RevisionTen\Forms\Command\FormDeleteCommand;
use RevisionTen\Forms\Command\FormEditCommand;
use RevisionTen\Forms\Command\FormEditItemCommand;
use RevisionTen\Forms\Command\FormRemoveItemCommand;
use RevisionTen\Forms\Command\FormShiftItemCommand;
use RevisionTen\Forms\Form\FormType;
use RevisionTen\Forms\Form\ItemType;
use RevisionTen\Forms\Handler\FormBaseHandler;
use RevisionTen\Forms\Interfaces\ItemInterface;
use RevisionTen\Forms\Model\Form;
use RevisionTen\Forms\Model\FormRead;
use RevisionTen\Forms\Model\FormSubmission;
use RevisionTen\Forms\Services\FormService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FormController.
 *
 * @Route("/admin/forms")
 */
class FormController extends Controller
{
    /**
     * TODO: Put this in a cqrs helper bundle.
     * Returns the difference between base array and change array.
     * Works with multidimensional arrays.
     *
     * @param array $base
     * @param array $change
     *
     * @return array
     */
    private function diff(array $base, array $change): array
    {
        $diff = [];

        foreach ($change as $property => $value) {
            $equal = true;

            if (!array_key_exists($property, $base)) {
                // Property is new.
                $equal = false;
            } else {
                $originalValue = $base[$property];

                if (is_array($value) && is_array($originalValue)) {
                    // Check if values arrays are identical.
                    if (0 !== strcmp(json_encode($value), json_encode($originalValue))) {
                        // Arrays are not equal.
                        $equal = false;
                    }
                } elseif ($originalValue !== $value) {
                    $equal = false;
                }
            }

            if (!$equal) {
                $diff[$property] = $value;
            }
        }

        return $diff;
    }

    /**
     * Returns info from the messageBus.
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function errorResponse(string $formUuid = null)
    {
        /** @var MessageBus $messageBus */
        $messageBus = $this->get('messagebus');
        $messages = $messageBus->getMessagesJson();

        if ($formUuid) {
            foreach ($messages as $message) {
                $this->addFlash(
                    'warning',
                    $message['message']
                );
            }

            return $this->redirectToForm($formUuid);
        } else {
            return new JsonResponse($messages);
        }
    }

    /**
     * Redirects to the edit page of a Form Aggregate by its uuid.
     *
     * @param string $formUuid
     *
     * @return Response
     */
    private function redirectToForm(string $formUuid): Response
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /** @var FormRead|null $formRead */
        $formRead = $em->getRepository(FormRead::class)->findOneByUuid($formUuid);

        if ($formRead) {
            return $this->redirectToRoute('forms_edit_aggregate', [
                'id' => $formRead->getId(),
            ]);
        } else {
            return $this->redirect('/admin');
        }
    }

    /**
     * Displays the Form Aggregate create form.
     *
     * @Route("/create-form", name="forms_create_form")
     *
     * @param Request    $request
     * @param CommandBus $commandBus
     *
     * @return Response
     */
    public function createFormAggregate(Request $request, CommandBus $commandBus)
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $aggregateUuid = Uuid::uuid1()->toString();

            // Execute Command.
            $success = false;
            $commandBus->dispatch(new FormCreateCommand($user->getId(), Uuid::uuid1()->toString(), $aggregateUuid, 0, $data, function ($commandBus, $event) use (&$success) {
                // Callback.
                $success = true;
            }));

            if ($success) {
                $this->addFlash(
                    'success',
                    'Form created'
                );

                return $this->redirectToForm($aggregateUuid);
            } else {
                return $this->errorResponse($aggregateUuid);
            }
        }

        return $this->render('@forms/Form/form.html.twig', array(
            'title' => 'Add Form',
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/delete-aggregate", name="forms_delete_aggregate")
     *
     * @param Request                $request
     * @param CommandBus             $commandBus
     * @param AggregateFactory       $aggregateFactory
     * @param EntityManagerInterface $em
     *
     * @return Response
     */
    public function deleteAggregateAction(Request $request, CommandBus $commandBus, AggregateFactory $aggregateFactory, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var int $id FormRead Id. */
        $id = $request->get('id');
        /** @var FormRead $formRead */
        $formRead = $em->getRepository(FormRead::class)->find($id);

        if (null === $formRead) {
            return $this->redirect('/admin');
        }

        $formUuid = $formRead->getUuid();
        /** @var Form $formAggregate */
        $formAggregate = $aggregateFactory->build($formUuid, Form::class, null, $user->getId());

        // Execute Command.
        $success = false;
        $commandBus->dispatch(new FormDeleteCommand($user->getId(), Uuid::uuid1()->toString(), $formAggregate->getUuid(), $formAggregate->getStreamVersion(), [], function ($commandBus, $event) use (&$success) {
            // Callback.
            $success = true;
        }));

        if ($success) {
            return $this->redirect('/admin/?entity=FormRead&action=list');
        } else {
            return $this->errorResponse($formAggregate->getUuid());
        }
    }

    /**
     * @Route("/edit-aggregate", name="forms_edit_aggregate")
     *
     * @param Request                $request
     * @param CommandBus             $commandBus
     * @param AggregateFactory       $aggregateFactory
     * @param FormService            $formService
     * @param EntityManagerInterface $em
     *
     * @return Response
     */
    public function editAggregateAction(Request $request, CommandBus $commandBus, AggregateFactory $aggregateFactory, FormService $formService, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var int $id FormRead Id. */
        $id = $request->get('id');
        /** @var FormRead $formRead */
        $formRead = $em->getRepository(FormRead::class)->find($id);

        if (null === $formRead) {
            return $this->redirect('/admin');
        }

        $formUuid = $formRead->getUuid();
        /** @var Form $formAggregate */
        $formAggregate = $aggregateFactory->build($formUuid, Form::class, null, $user->getId());
        // Convert Aggregate to data array for form and remove properties we don't want changed.
        $aggregateData = json_decode(json_encode($formAggregate), true);

        // Get item variables.
        $itemVariables = [];
        if (isset($aggregateData['items']) && is_array($aggregateData['items']) && count($aggregateData['items']) > 0) {
            foreach ($aggregateData['items'] as $item) {
                $itemType = $formService->getItemClass($item['itemName']);
                $itemVariables[] = $itemType::getVariables($item['data']);
            }
        }

        // Create Edit Form.
        unset($aggregateData['uuid']);
        unset($aggregateData['items']);
        $form = $this->createForm(FormType::class, $aggregateData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // Remove data that hasn't changed.
            $data = $this->diff($aggregateData, $data);

            // Execute Command.
            $success = false;
            $commandBus->dispatch(new FormEditCommand($user->getId(), Uuid::uuid1()->toString(), $formAggregate->getUuid(), $formAggregate->getStreamVersion(), $data, function ($commandBus, $event) use (&$success) {
                // Callback.
                $success = true;
            }));

            if ($success) {
                $this->addFlash(
                    'success',
                    'Form edited'
                );

                return $this->redirectToForm($formUuid);
            } else {
                return $this->errorResponse($formAggregate->getUuid());
            }
        }

        if (class_exists('\Symfony\Component\HttpKernel\Kernel')) {
            $symfony4 = '3' === substr(\Symfony\Component\HttpKernel\Kernel::VERSION, 0, 1) ? false : true;
        } else {
            $symfony4 = true;
        }

        return $this->render('@forms/Admin/edit-aggregate.html.twig', [
            'form' => $form->createView(),
            'formRead' => $formRead,
            'formAggregate' => $formAggregate,
            'user' => $user,
            'config' => $this->getParameter('forms'),
            'itemVariables' => $itemVariables,
            'symfony4' => $symfony4,
        ]);
    }

    /**
     * @param FormService $formService
     * @param string      $itemName
     * @param array|null  $data
     * @param array|null  $items
     *
     * @return FormInterface
     */
    private function getItemForm(FormService $formService, string $itemName, array $data = null, array $items = null): FormInterface
    {
        $data = $data ?? ['data' => []];
        $formClass = $formService->getItemClass($itemName);

        return $this->createForm(ItemType::class, $data, [
            'formClass' => $formClass,
            'items' => $items,
        ]);
    }

    /**
     * @Route("/add-item/{formUuid}/{onVersion}/{itemName}/{parent}", name="forms_add_item")
     *
     * @param Request             $request
     * @param CommandBus          $commandBus
     * @param AggregateFactory    $aggregateFactory
     * @param FormService         $formService
     * @param TranslatorInterface $translator
     * @param string              $formUuid
     * @param int                 $onVersion
     * @param string|null         $parent
     * @param string              $itemName
     *
     * @return Response
     */
    public function formAddItem(Request $request, CommandBus $commandBus, AggregateFactory $aggregateFactory, FormService $formService, TranslatorInterface $translator, string $formUuid, int $onVersion, string $parent = null, string $itemName): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Form $aggregate */
        $aggregate = $aggregateFactory->build($formUuid, Form::class, $onVersion, $user->getId());

        $form = $this->getItemForm($formService, $itemName, null, $aggregate->items);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData()['data'];

            // Execute Command.
            $success = false;
            $commandBus->dispatch(new FormAddItemCommand($user->getId(), Uuid::uuid1()->toString(), $formUuid, $onVersion, [
                'itemName' => $itemName,
                'data' => $data,
                'parent' => $parent,
            ], function ($commandBus, $event) use (&$success) {
                // Callback.
                $success = true;
            }));

            if ($success) {
                $this->addFlash(
                    'success',
                    'Field added'
                );

                return $this->redirectToForm($formUuid);
            } else {
                return $this->errorResponse($formUuid);
            }
        }

        $itemNameTranslated = $translator->trans($itemName);
        $title = $translator->trans('Add %itemName% field', ['%itemName%' => $itemNameTranslated]);

        return $this->render('@forms/Form/form.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit-item/{formUuid}/{onVersion}/{itemUuid}", name="forms_edit_item")
     *
     * @param Request             $request
     * @param CommandBus          $commandBus
     * @param AggregateFactory    $aggregateFactory
     * @param FormService         $formService
     * @param TranslatorInterface $translator
     * @param string              $formUuid
     * @param int                 $onVersion
     * @param string              $itemUuid
     *
     * @return Response
     */
    public function formEditItem(Request $request, CommandBus $commandBus, AggregateFactory $aggregateFactory, FormService $formService, TranslatorInterface $translator, string $formUuid, int $onVersion, string $itemUuid): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Form $aggregate */
        $aggregate = $aggregateFactory->build($formUuid, Form::class, $onVersion, $user->getId());

        if (empty($aggregate->items)) {
            // Aggregate does not exist, or is empty.
            return $this->redirectToForm($formUuid);
        }

        // Get the item from the Aggregate.
        $item = FormBaseHandler::getItem($aggregate, $itemUuid);

        if ($item && isset($item['data']) && isset($item['itemName'])) {
            $data = $item;
            $itemName = $item['itemName'];

            $form = $this->getItemForm($formService, $itemName, $data, $aggregate->items);
            $form->handleRequest($request);

            // Get differences in data and check if data has changed.
            if ($form->isSubmitted()) {
                $data = $form->getData()['data'];
                // Remove data that hasn't changed.
                $data = $this->diff($item['data'], $data);

                if (empty($data)) {
                    $form->addError(new FormError($translator->trans('Data has not changed.')));
                }
            }

            if ($form->isSubmitted() && $form->isValid()) {
                // Execute Command.
                $success = false;
                $commandBus->dispatch(new FormEditItemCommand($user->getId(), Uuid::uuid1()->toString(), $formUuid, $onVersion, [
                    'uuid' => $itemUuid,
                    'data' => $data,
                ], function ($commandBus, $event) use (&$success) {
                    // Callback.
                    $success = true;
                }));

                if ($success) {
                    $this->addFlash(
                        'success',
                        $translator->trans('Field edited')
                    );

                    return $this->redirectToForm($formUuid);
                } else {
                    return $this->errorResponse($formUuid);
                }
            }

            $itemNameTranslated = $translator->trans($itemName);
            $title = $translator->trans('Edit %itemName% field', ['%itemName%' => $itemNameTranslated]);

            return $this->render('@forms/Form/form.html.twig', [
                'title' => $title,
                'form' => $form->createView(),
            ]);
        }
    }

    /**
     * Removes an item from a Form Aggregate.
     *
     * @Route("/remove-item/{formUuid}/{onVersion}/{itemUuid}", name="forms_remove_item")
     *
     * @param CommandBus          $commandBus
     * @param TranslatorInterface $translator
     * @param string              $formUuid
     * @param int                 $onVersion
     * @param string              $itemUuid
     *
     * @return JsonResponse|Response
     */
    public function formRemoveItem(CommandBus $commandBus, TranslatorInterface $translator, string $formUuid, int $onVersion, string $itemUuid)
    {
        /** @var User $user */
        $user = $this->getUser();

        $success = false;
        $commandBus->dispatch(new FormRemoveItemCommand($user->getId(), Uuid::uuid1()->toString(), $formUuid, $onVersion, [
            'uuid' => $itemUuid,
        ], function ($commandBus, $event) use (&$success) {
            // Callback.
            $success = true;
        }));

        if ($success) {
            $this->addFlash(
                'success',
                $translator->trans('Field deleted')
            );

            return $this->redirectToForm($formUuid);
        } else {
            return $this->errorResponse($formUuid);
        }
    }

    /**
     * Shift a item up or down on a Form Aggregate.
     *
     * @Route("/shift-item/{formUuid}/{onVersion}/{itemUuid}/{direction}", name="forms_shift_item")
     *
     * @param CommandBus          $commandBus
     * @param TranslatorInterface $translator
     * @param string              $formUuid
     * @param int                 $onVersion
     * @param string              $itemUuid
     * @param string              $direction
     *
     * @return JsonResponse|Response
     */
    public function formShiftItem(CommandBus $commandBus, TranslatorInterface $translator, string $formUuid, int $onVersion, string $itemUuid, string $direction)
    {
        /** @var User $user */
        $user = $this->getUser();

        $success = false;
        $commandBus->dispatch(new FormShiftItemCommand($user->getId(), Uuid::uuid1()->toString(), $formUuid, $onVersion, [
            'uuid' => $itemUuid,
            'direction' => $direction,
        ], function ($commandBus, $event) use (&$success) {
            // Callback.
            $success = true;
        }));

        if ($success) {
            $this->addFlash(
                'success',
                $translator->trans('Field shifted')
            );

            return $this->redirectToForm($formUuid);
        } else {
            return $this->errorResponse($formUuid);
        }
    }

    /**
     * Check the aggregate for a field with the propertyName boolean
     * property and return the fields value.
     *
     * Example: You have a field with a "replyTo" boolean and want to return
     * its value when it is set to true.
     *
     * Todo: Support child items.
     *
     * @param array  $payload
     * @param array  $data
     * @param string $propertyName
     *
     * @return mixed|null
     */
    private function getField(array $payload, array $data, string $propertyName)
    {
        $isField = false;
        $payload = json_decode(json_encode($payload), true);
        if ($payload['items'] && is_array($payload['items']) && count($payload['items']) > 0) {
            foreach ($payload['items'] as $item) {
                if (isset($item['data'][$propertyName]) && $item['data'][$propertyName]) {
                    $isField = $item['data']['name'];
                }
            }
        }

        return $isField ? $data[$isField] : null;
    }

    /**
     * TODO: Autowire everything.
     *
     * @param string $formUuid
     * @param string $template
     * @param array  $defaultData
     *
     * @return Response
     */
    public function renderFormAction(string $formUuid, string $template = '@forms/Frontend/form.html.twig', array $defaultData): Response
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // Get the FormRead Entity.
        /** @var FormRead $formRead */
        $formRead = $em->getRepository(FormRead::class)->findOneByUuid($formUuid);

        /** @var RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getMasterRequest();

        /** @var FormService $formService */
        $formService = $this->get('formservice');

        $ignore_validation = (bool) $request->get('ignore_validation');

        $form = $formService->getForm($formUuid, $defaultData, $ignore_validation);
        $form->handleRequest($request);

        #$hasTimeLimitCookie = $request->cookies->has('submitLimit') ? true : false;
        $hasTimeLimitCookie = false;
        $ip = $request->getClientIp();

        // Check for recent submissions by this ip on this form.
        /** @var FormSubmission $formSubmission */
        $formSubmission = $em->getRepository(FormSubmission::class)->findOneBy([
            'ip' => $ip,
            'form' => $formRead->getId(),
        ], [
            'created' => Criteria::DESC,
        ]);

        $hasIpBlock = false;
        if ($formSubmission && time() < $formSubmission->getExpires()->getTimestamp()) {
            $hasIpBlock = true;
        }

        $submittedData = [];
        foreach ($form->all() as $fieldName => $fieldValue) {
            $submittedData[$fieldName] = $fieldValue->getData();
        }

        if (!$ignore_validation && ($hasTimeLimitCookie || $hasIpBlock)) {
            $aggregateData = json_decode(json_encode($formRead->getPayload()), true);
            $timeLimitMessage = $aggregateData['timeLimitMessage'] ?? $this->get('translator')->trans('You have already submitted the form, please try again later');
            if ($form->isSubmitted()) {
                $form->addError(new FormError($timeLimitMessage));
            } else {
                $this->addFlash(
                    'warning',
                    $timeLimitMessage
                );
            }
        }

        if (!$ignore_validation && !$hasTimeLimitCookie && $form->isSubmitted()) {

            $data = $submittedData; // $form->getData();
            $aggregateData = json_decode(json_encode($formRead->getPayload()), true);

            // Execute onValidate listeners.
            foreach ($aggregateData['items'] as $item) {
                $itemClass = $formService->getItemClass($item['itemName']);
                // Get the form as a service or instanciate it.
                try {
                    $itemForm = $this->get($itemClass);
                } catch (ServiceNotFoundException $exception) {
                    $itemForm = new $itemClass();
                }

                if ($itemForm instanceof ItemInterface) {
                    if (!$itemForm->onValidate($data, $item['data'], $formRead, $form)) {
                        break;
                    }
                }
            }
        }


        if (!$ignore_validation && !$hasTimeLimitCookie && $form->isSubmitted() && $form->isValid()) {

            // Build and send the email.
            if ($emailTemplate = $formRead->getEmailTemplate()) {

                /** @var FormError[] $errors */
                $errors = [];
                $isValid = true;

                // Execute onSubmit listeners.
                foreach ($aggregateData['items'] as $item) {
                    $itemClass = $formService->getItemClass($item['itemName']);
                    // Get the form as a service or instanciate it.
                    try {
                        $itemForm = $this->get($itemClass);
                    } catch (ServiceNotFoundException $exception) {
                        $itemForm = new $itemClass();
                    }

                    if ($itemForm instanceof ItemInterface) {
                        if (!$itemForm->onSubmit($data, $item['data'], $formRead, $form)) {
                            $isValid = false;
                            /*foreach ($errors as $error) {
                                if ($form->has($item['data']['name'])) {
                                    $form->get($item['data']['name'])->addError($error);
                                } else {
                                    $form->addError($error);
                                }
                            }*/
                            break;
                        }
                    }
                }

                if ($isValid) {
                    $message = new \Swift_Message();

                    // Get To Email.
                    $to = $this->getField($aggregateData, $data, 'isReceiver') ?? $formRead->getEmail();
                    $message->setTo(self::getMailsFromString($to));

                    // Get CC Email.
                    $cc = $formRead->getEmailCC();
                    if ($cc) {
                        $message->setCc(self::getMailsFromString($cc));
                    }

                    // Get BCC Email.
                    $bcc = $formRead->getEmailBCC();
                    if ($bcc) {
                        $message->setBcc(self::getMailsFromString($bcc));
                    }

                    // Get ReplyTo Email.
                    $replyTo = $this->getField($aggregateData, $data, 'replyTo');

                    // Get ReplyTo Name.
                    $replyToName = null;
                    $name = $this->getField($aggregateData, $data, 'isName');
                    $firstname = $this->getField($aggregateData, $data, 'isFirstname');
                    if ($replyTo && $name && is_string($name)) {
                        $replyToName = $name;
                        if ($firstname && is_string($firstname)) {
                            $replyToName = $firstname.' '.$replyToName;
                        }
                    }

                    // Set Reply To.
                    $message->setReplyTo($replyTo, $replyToName);

                    // Set Sender and From.
                    $message->setSender($formRead->getSender(), $replyToName ?? 'Website');
                    $message->setFrom($formRead->getSender(), $replyToName ?? 'Website');

                    // Get Subject.
                    $message->setSubject($this->getField($aggregateData, $data, 'isSubject') ?? $formRead->getTitle());

                    // Try to render the twig template.
                    /** @var \Twig_Environment $twig */
                    $twig = $this->get('twig');
                    $templateFailed = false;
                    try {
                        $view = $twig->createTemplate($emailTemplate);
                        try {
                            $body = $view->render($data);
                        } catch (\Twig_Error_Runtime $e) {
                            $templateFailed = true;
                        } catch (\Throwable $e) {
                            $templateFailed = true;
                        }
                    } catch (\Twig_Error_Syntax $e) {
                        $templateFailed = true;
                    } catch (\Twig_Error_Loader $e) {
                        $templateFailed = true;
                    }

                    // If rendering the twig template fails json_encode the raw form data and send as plain text with error attached.
                    if ($templateFailed) {
                        $body = 'An Error occurred: '.$e->getRawMessage()."\nPlease check your Email-Template at line ".$e->getTemplateLine().". \nHere is the raw form submission:";
                        $body .= "\n\n".json_encode($request->request->all());
                        $formRead->setHtml(false);
                    }

                    $message->setBody($body ?? 'ERROR', $formRead->getHtml() ? 'text/html' : 'text/plain');

                    $this->get('mailer')->send($message);
                    // Display Success Message.
                    $this->addFlash(
                        'success',
                        $formRead->getSuccessText()
                    );

                    $seconds = $aggregateData['timelimit'] ?? false;
                    if ($seconds) {
                        $expiresTimestamp = time() + intval($seconds);
                        #setcookie('submitLimit', '1', $expiresTimestamp);
                        $expires = new \DateTime();
                        $expires->setTimestamp($expiresTimestamp);
                        $formSubmission = new FormSubmission($formRead, $ip, $expires);
                        $em->persist($formSubmission);
                        $em->flush();
                    }
                }
            } else {
                $this->addFlash(
                    'danger',
                    $this->get('translator')->trans('An Error occurred.')
                );
            }
        }

        return $this->render($template, [
            'form' => $form->createView(),
            'request' => $request,
            'ignore_validation' => $ignore_validation,
        ]);
    }

    /**
     * @param string $mails
     *
     * @return array
     */
    private static function getMailsFromString(string $mails): array
    {
        return array_map('trim', explode(',', $mails));
    }

    /**
     * @Route("/clone-aggregate", name="forms_clone_aggregate")
     *
     * @param Request                $request
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     *
     * @return Response
     */
    public function cloneAggregateAction(Request $request, CommandBus $commandBus, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var int $id FormRead Id. */
        $id = $request->get('id');
        /** @var FormRead $formRead */
        $formRead = $em->getRepository(FormRead::class)->find($id);

        if (null === $formRead) {
            return $this->redirect('/admin');
        }

        $originalUuid = $formRead->getUuid();
        $originalVersion = $formRead->getVersion();

        $data = [
            'originalUuid' => $originalUuid,
            'originalVersion' => $originalVersion,
        ];
        $aggregateUuid = Uuid::uuid1()->toString();

        // Execute Command.
        $success = false;
        $commandBus->dispatch(new FormCloneCommand($user->getId(), Uuid::uuid1()->toString(), $aggregateUuid, 0, $data, function ($commandBus, $event) use (&$success) {
            // Callback.
            $success = true;
        }));

        if ($success) {
            $this->addFlash(
                'success',
                $translator->trans('Form duplicated')
            );

            return $this->redirectToForm($aggregateUuid);
        } else {
            return $this->errorResponse($aggregateUuid);
        }
    }
}
