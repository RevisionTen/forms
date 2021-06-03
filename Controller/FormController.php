<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Controller;

use Exception;
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
use RevisionTen\Forms\Entity\FormRead;
use RevisionTen\Forms\Entity\FormSubmission;
use RevisionTen\Forms\Services\FormService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_intersect_key;
use function array_key_exists;
use function array_map;
use function class_exists;
use function count;
use function defined;
use function is_array;
use function json_decode;
use function json_encode;
use function rsort;
use function strcmp;

/**
 * Class FormController.
 *
 * @Route("/admin/forms")
 */
class FormController extends AbstractController
{
    private MessageBus $messageBus;

    private CommandBus $commandBus;

    private AggregateFactory $aggregateFactory;

    private EntityManagerInterface $entityManager;

    private FormService $formService;

    private TranslatorInterface $translator;

    public function __construct(MessageBus $messageBus, CommandBus $commandBus, AggregateFactory $aggregateFactory, EntityManagerInterface $entityManager, FormService $formService, TranslatorInterface $translator)
    {
        $this->messageBus = $messageBus;
        $this->commandBus = $commandBus;
        $this->aggregateFactory = $aggregateFactory;
        $this->entityManager = $entityManager;
        $this->formService = $formService;
        $this->translator = $translator;
    }

    /**
     * TODO: Put this in a cqrs helper bundle.
     * Returns the difference between base array and change array.
     * Works with multidimensional arrays.
     *
     * @param array $base
     * @param array $change
     *
     * @return array
     * @throws Exception
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
     * @param string|null $formUuid
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function errorResponse(string $formUuid = null)
    {
        $messages = $this->messageBus->getMessagesJson();

        if ($formUuid) {
            foreach ($messages as $message) {
                $this->addFlash(
                    'warning',
                    $message['message']
                );
            }

            return $this->redirectToForm($formUuid);
        }

        return new JsonResponse($messages);
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
        /**
         * @var FormRead|null $formRead
         */
        $formRead = $this->entityManager->getRepository(FormRead::class)->findOneBy([
            'uuid' => $formUuid,
        ]);

        if ($formRead) {
            return $this->redirectToRoute('forms_edit_aggregate', [
                'id' => $formRead->getId(),
            ]);
        }

        return $this->redirect('/admin');
    }

    /**
     * Displays the Form Aggregate create form.
     *
     * @Route("/create-form", name="forms_create_form")
     *
     * @param Request $request
     *
     * @return JsonResponse|RedirectResponse|Response
     * @throws Exception
     */
    public function createFormAggregate(Request $request)
    {
        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $aggregateUuid = Uuid::uuid1()->toString();

            // Execute Command.
            $success = $this->commandBus->dispatch(new FormCreateCommand(
                $user->getId(),
                Uuid::uuid1()->toString(),
                $aggregateUuid,
                0,
                $data
            ));

            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Form created', [], 'cms')
                );

                return $this->redirectToForm($aggregateUuid);
            }

            return $this->errorResponse($aggregateUuid);
        }

        return $this->render('@CMS/Backend/Form/form.html.twig', [
            'title' => $this->translator->trans('forms.label.addForm', [], 'cms'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete-aggregate", name="forms_delete_aggregate")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function deleteAggregateAction(Request $request): Response
    {
        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        /**
         * @var int $id FormRead Id.
         */
        $id = $request->get('id');

        /**
         * @var FormRead $formRead
         */
        $formRead = $this->entityManager->getRepository(FormRead::class)->find($id);

        if (null === $formRead) {
            return $this->redirect('/admin');
        }

        $formUuid = $formRead->getUuid();
        /**
         * @var Form $formAggregate
         */
        $formAggregate = $this->aggregateFactory->build($formUuid, Form::class, null, $user->getId());

        // Execute Command.
        $success = $this->commandBus->dispatch(new FormDeleteCommand(
            $user->getId(),
            Uuid::uuid1()->toString(),
            $formAggregate->getUuid(),
            $formAggregate->getStreamVersion(),
            []
        ));

        return $success ? $this->redirect('/admin/?entity=FormRead&action=list') : $this->errorResponse($formAggregate->getUuid());
    }

    /**
     * @Route("/edit-aggregate", name="forms_edit_aggregate")
     *
     * @param Request $request
     * @param ContainerInterface $container
     *
     * @return Response
     * @throws Exception
     */
    public function editAggregateAction(Request $request, ContainerInterface $container): Response
    {
        $config = $container->getParameter('forms');

        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        /**
         * @var int $id FormRead Id.
         */
        $id = $request->get('id');

        /**
         * @var FormRead $formRead
         */
        $formRead = $this->entityManager->getRepository(FormRead::class)->find($id);

        if (null === $formRead) {
            return $this->redirect('/admin');
        }

        $formUuid = $formRead->getUuid();
        /**
         * @var Form $formAggregate
         */
        $formAggregate = $this->aggregateFactory->build($formUuid, Form::class, null, $user->getId());
        // Convert Aggregate to data array for form and remove properties we don't want changed.
        $aggregateData = json_decode(json_encode($formAggregate), true, 512);

        // Get item variables.
        $itemVariables = [];
        if (isset($aggregateData['items']) && is_array($aggregateData['items']) && count($aggregateData['items']) > 0) {
            foreach ($aggregateData['items'] as $item) {
                /**
                 * @var ItemInterface $itemType
                 */
                $itemType = $this->formService->getItemClass($item['itemName']);
                $itemVariables[] = $itemType::getVariables($item['data']);
            }
        }

        // Create Edit Form.
        unset($aggregateData['uuid'], $aggregateData['items']);
        $form = $this->createForm(FormType::class, $aggregateData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // Remove data that hasn't changed.
            $data = $this->diff($aggregateData, $data);

            // Execute Command.
            $success = $this->commandBus->dispatch(new FormEditCommand(
                $user->getId(),
                Uuid::uuid1()->toString(),
                $formAggregate->getUuid(),
                $formAggregate->getStreamVersion(),
                $data
            ));

            if ($success) {
                $this->addFlash(
                    'success',
                    'Form edited'
                );

                return $this->redirectToForm($formUuid);
            }

            return $this->errorResponse($formAggregate->getUuid());
        }

        return $this->render('@forms/Admin/edit-aggregate.html.twig', [
            'form' => $form->createView(),
            'title' => $this->translator->trans('forms.label.editForm', [
                '%title%' => $formAggregate->title,
            ], 'cms'),
            'formRead' => $formRead,
            'formAggregate' => $formAggregate,
            'user' => $user,
            'config' => $config,
            'itemVariables' => $itemVariables,
        ]);
    }

    /**
     * @param string     $itemName
     * @param array|null $data
     * @param array|null $items
     *
     * @return FormInterface
     */
    private function getItemForm(string $itemName, array $data = null, array $items = null): FormInterface
    {
        $data = $data ?? ['data' => []];
        $formClass = $this->formService->getItemClass($itemName);

        return $this->createForm(ItemType::class, $data, [
            'formClass' => $formClass,
            'items' => $items,
        ]);
    }

    /**
     * @Route("/add-item/{formUuid}/{onVersion}/{itemName}/{parent}", name="forms_add_item")
     *
     * @param Request     $request
     * @param string      $formUuid
     * @param int         $onVersion
     * @param string|null $parent
     * @param string      $itemName
     *
     * @throws Exception
     * @return Response
     */
    public function formAddItem(Request $request, string $formUuid, int $onVersion, string $parent = null, string $itemName): Response
    {
        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        /**
         * @var Form $aggregate
         */
        $aggregate = $this->aggregateFactory->build($formUuid, Form::class, $onVersion, $user->getId());

        $form = $this->getItemForm($itemName, null, $aggregate->items);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData()['data'];

            // Execute Command.
            $success = $this->commandBus->dispatch(new FormAddItemCommand(
                $user->getId(),
                Uuid::uuid1()->toString(),
                $formUuid,
                $onVersion,
                [
                    'itemName' => $itemName,
                    'data' => $data,
                    'parent' => $parent,
                ]
            ));

            if ($success) {
                $this->addFlash(
                    'success',
                    'Field added'
                );

                return $this->redirectToForm($formUuid);
            }

            return $this->errorResponse($formUuid);
        }

        $itemNameTranslated = $this->translator->trans($itemName, [], 'cms');
        $title = $this->translator->trans('forms.label.addField', ['%itemName%' => $itemNameTranslated], 'cms');

        return $this->render('@CMS/Backend/Form/form.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit-item/{formUuid}/{onVersion}/{itemUuid}", name="forms_edit_item")
     *
     * @param Request $request
     * @param string  $formUuid
     * @param int     $onVersion
     * @param string  $itemUuid
     *
     * @throws Exception
     * @return Response
     */
    public function formEditItem(Request $request, string $formUuid, int $onVersion, string $itemUuid): Response
    {
        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        /**
         * @var Form $aggregate
         */
        $aggregate = $this->aggregateFactory->build($formUuid, Form::class, $onVersion, $user->getId());

        if (empty($aggregate->items)) {
            // Aggregate does not exist, or is empty.
            return $this->redirectToForm($formUuid);
        }

        // Get the item from the aggregate.
        $item = FormBaseHandler::getItem($aggregate, $itemUuid);

        // Get the form title.
        $title = $this->translator->trans('forms.label.editField', [
            '%itemName%' => $this->translator->trans($item['itemName'] ?? 'Form', [], 'cms'),
        ], 'cms');

        if ($item && isset($item['data'], $item['itemName'])) {
            $form = $this->getItemForm($item['itemName'], $item, $aggregate->items);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                // Get differences in data and check if data has changed.
                $data = $form->getData()['data'];
                // Remove data that hasn't changed.
                $data = $this->diff($item['data'], $data);
                if (empty($data)) {
                    $form->addError(new FormError($this->translator->trans('admin.validation.dataUnchanged', [], 'cms')));
                }

                if ($form->isValid()) {
                    // Execute Command.
                    $success = $this->commandBus->dispatch(new FormEditItemCommand(
                        $user->getId(),
                        Uuid::uuid1()->toString(),
                        $formUuid,
                        $onVersion,
                        [
                            'uuid' => $itemUuid,
                            'data' => $data,
                        ]
                    ));

                    if ($success) {
                        $this->addFlash(
                            'success',
                            $this->translator->trans('Field edited', [], 'cms')
                        );

                        return $this->redirectToForm($formUuid);
                    }

                    return $this->errorResponse($formUuid);
                }
            }
        } else {
            return $this->errorResponse($formUuid);
        }

        return $this->render('@CMS/Backend/Form/form.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Removes an item from a Form Aggregate.
     *
     * @Route("/remove-item/{formUuid}/{onVersion}/{itemUuid}", name="forms_remove_item")
     *
     * @param string $formUuid
     * @param int    $onVersion
     * @param string $itemUuid
     *
     * @throws Exception
     * @return JsonResponse|Response
     */
    public function formRemoveItem(string $formUuid, int $onVersion, string $itemUuid)
    {
        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        $success = $this->commandBus->dispatch(new FormRemoveItemCommand(
            $user->getId(),
            Uuid::uuid1()->toString(),
            $formUuid,
            $onVersion,
            [
                'uuid' => $itemUuid,
            ]
        ));

        if ($success) {
            $this->addFlash(
                'success',
                $this->translator->trans('Field deleted', [], 'cms')
            );

            return $this->redirectToForm($formUuid);
        }

        return $this->errorResponse($formUuid);
    }

    /**
     * Shift a item up or down on a Form Aggregate.
     *
     * @Route("/shift-item/{formUuid}/{onVersion}/{itemUuid}/{direction}", name="forms_shift_item")
     *
     * @param string $formUuid
     * @param int    $onVersion
     * @param string $itemUuid
     * @param string $direction
     *
     * @throws Exception
     * @return JsonResponse|RedirectResponse|Response
     */
    public function formShiftItem(string $formUuid, int $onVersion, string $itemUuid, string $direction)
    {
        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        $success = $this->commandBus->dispatch(new FormShiftItemCommand(
            $user->getId(),
            Uuid::uuid1()->toString(),
            $formUuid,
            $onVersion,
            [
                'uuid' => $itemUuid,
                'direction' => $direction,
            ]
        ));

        if ($success) {
            $this->addFlash(
                'success',
                $this->translator->trans('Field shifted', [], 'cms')
            );

            return $this->redirectToForm($formUuid);
        }

        return $this->errorResponse($formUuid);
    }

    /**
     * @param RequestStack $requestStack
     * @param string       $formUuid
     * @param string|null  $template
     * @param array        $defaultData
     *
     * @return Response
     * @throws Exception
     */
    public function renderCmsForm(RequestStack $requestStack, string $formUuid, string $template = null, array $defaultData): Response
    {
        $request = $requestStack->getMainRequest();
        $handledRequest = $this->formService->handleRequest($request, $formUuid, $defaultData);

        // Get the forms template.
        $baseTemplate = $handledRequest['template'] ?? '@forms/Frontend/form.html.twig';
        $template = $template ?: $baseTemplate;

        foreach ($handledRequest['messages'] as $message) {
            $this->addFlash(
                $message['type'],
                $message['message']
            );
        }

        return $this->render($template, [
            'form' => $handledRequest['formView'],
            'request' => $request,
            'ignore_validation' => $handledRequest['ignore_validation'],
            'scrollToSuccessText' => $handledRequest['scrollToSuccessText'],
        ]);
    }

    /**
     * @Route("/clone-aggregate", name="forms_clone_aggregate")
     *
     * @param Request $request
     *
     * @throws Exception
     * @return Response
     */
    public function cloneAggregateAction(Request $request): Response
    {
        /**
         * @var UserInterface $user
         */
        $user = $this->getUser();

        /**
         * @var int $id FormRead Id.
         */
        $id = $request->get('id');

        /**
         * @var FormRead $formRead
         */
        $formRead = $this->entityManager->getRepository(FormRead::class)->find($id);

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
        $success = $this->commandBus->dispatch(new FormCloneCommand(
            $user->getId(),
            Uuid::uuid1()->toString(),
            $aggregateUuid,
            0,
            $data
        ));

        if ($success) {
            $this->addFlash(
                'success',
                $this->translator->trans('Form duplicated', [], 'cms')
            );

            return $this->redirectToForm($aggregateUuid);
        }

        return $this->errorResponse($aggregateUuid);
    }

    /**
     * @Route("/submissions-download", name="forms_submissions_download")
     *
     * @param SerializerInterface $serializer
     * @param Request             $request
     *
     * @return Response
     */
    public function submissionsDownload(SerializerInterface $serializer, Request $request): Response
    {
        $id = (int) $request->get('id');

        $submissions = $this->formService->getFormSubmissions($id);

        $submissionsCsv = $serializer->encode($submissions, 'csv');

        $response = new Response($submissionsCsv);

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="submissions.csv"');

        return $response;
    }
}
