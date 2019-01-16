<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Services;

use Doctrine\Common\Collections\Criteria;
use RevisionTen\Forms\Form\Items\TextItem;
use RevisionTen\Forms\Interfaces\ItemInterface;
use RevisionTen\Forms\Model\Form;
use RevisionTen\Forms\Model\FormRead;
use RevisionTen\CQRS\Services\AggregateFactory;
use Doctrine\ORM\EntityManagerInterface;
use RevisionTen\Forms\Model\FormSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FormService.
 */
class FormService
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AggregateFactory
     */
    private $aggregateFactory;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var array
     */
    private $config;

    /**
     * PagePublishListener constructor.
     *
     * @param ContainerInterface     $container
     * @param TranslatorInterface    $translator
     * @param \Swift_Mailer          $mailer
     * @param \Twig_Environment      $twig
     * @param EntityManagerInterface $entityManager
     * @param AggregateFactory       $aggregateFactory
     * @param FormFactoryInterface   $formFactory
     * @param array                  $config
     */
    public function __construct(ContainerInterface $container, TranslatorInterface $translator, \Swift_Mailer $mailer, \Twig_Environment $twig, EntityManagerInterface $entityManager, AggregateFactory $aggregateFactory, FormFactoryInterface $formFactory, array $config)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->aggregateFactory = $aggregateFactory;
        $this->formFactory = $formFactory;
        $this->config = $config;
    }

    /**
     * Update the FormRead entity for the admin backend.
     *
     * @param string $formUuid
     */
    public function updateFormRead(string $formUuid): void
    {
        /**
         * @var Form $aggregate
         */
        $aggregate = $this->aggregateFactory->build($formUuid, Form::class);

        // Build FormRead entity from Aggregate.
        $formRead = $this->entityManager->getRepository(FormRead::class)->findOneByUuid($formUuid) ?? new FormRead();
        $formRead->setVersion($aggregate->getStreamVersion());
        $formRead->setUuid($formUuid);

        $formData = json_decode(json_encode($aggregate), true);
        $formRead->setPayload($formData);

        $formRead->setTitle($aggregate->title);
        $formRead->setEmail($aggregate->email);
        $formRead->setEmailCC($aggregate->emailCC);
        $formRead->setEmailBCC($aggregate->emailBCC);
        $formRead->setSender($aggregate->sender);
        $formRead->setTemplate($aggregate->template);
        $formRead->setEmailTemplate($aggregate->emailTemplate);
        $formRead->setEmailTemplateCopy($aggregate->emailTemplateCopy);
        $formRead->setDeleted($aggregate->deleted);
        $formRead->setHtml($aggregate->html);
        $formRead->setSuccessText($aggregate->successText);
        $formRead->setSaveSubmissions($aggregate->saveSubmissions);
        $formRead->setCreated($aggregate->getCreated());
        $formRead->setModified($aggregate->getModified());

        // Persist FormRead entity.
        $this->entityManager->persist($formRead);
        $this->entityManager->flush();
    }

    /**
     * @param string $itemName
     *
     * @return string
     */
    public function getItemClass(string $itemName): string
    {
        $itemTypes = $this->config['item_types'] ?? [];

        return $itemTypes[$itemName]['class'] ?? TextItem::class;
    }

    /**
     * @param string $formUuid
     * @param null   $data
     * @param bool   $ignore_validation
     *
     * @return FormInterface
     */
    public function getForm(string $formUuid, $data = null, bool $ignore_validation = false): FormInterface
    {
        /** @var FormRead $formRead */
        $formRead = $this->entityManager->getRepository(FormRead::class)->findOneByUuid($formUuid);
        $payload = $formRead->getPayload();

        $formName = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $formRead->getTitle()));
        if (empty($formName)) {
            $formName = $formRead->getUuid();
        }

        $formBuilder = $this->formFactory->createNamedBuilder($formName, FormType::class, $data, [
            'action' => '?formular=abgeschickt',
        ]);

        foreach ($payload['items'] as $item) {
            $itemClass = $this->getItemClass($item['itemName']);

            if (method_exists($itemClass, 'getItem')) {
                $itemClass::getItem($formBuilder, $item['data']);
            }
        }

        if ($ignore_validation) {
            $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $event->stopPropagation();
            }, 900);
        }

        return $formBuilder->getForm();
    }

    /**
     * @param string $ip
     * @param int    $formId
     *
     * @return bool
     */
    public function isBlocked(string $ip, int $formId): bool
    {
        /**
         * Check for recent submissions by this ip on this form.
         *
         * @var FormSubmission[] $formSubmissions
         */
        $formSubmissions = $this->entityManager->getRepository(FormSubmission::class)->findBy([
            'ip' => $ip,
            'form' => $formId,
        ], [
            'created' => Criteria::DESC,
        ], 1);
        /** @var FormSubmission $formSubmission */
        $formSubmission = !empty($formSubmissions) ? array_values($formSubmissions)[0] : null;

        return null !== $formSubmission && time() < $formSubmission->getExpires()->getTimestamp();
    }

    public function handleRequest(Request $request, string $formUuid, array $defaultData): array
    {
        $messages = [];

        /**
         * Get the FormRead entity.
         *
         * @var FormRead $formRead
         */
        $formRead = $this->entityManager->getRepository(FormRead::class)->findOneByUuid($formUuid);

        $ignore_validation = null !== $request && $request->get('ignore_validation');
        $form = $this->getForm($formUuid, $defaultData, $ignore_validation);
        $form->handleRequest($request);

        // Check If user is blocked.
        $ip = $request->getClientIp();
        $isBlocked = $ip ? $this->isBlocked($ip, $formRead->getId()) : false;

        // Display error If user is blocked.
        if (!$ignore_validation && $isBlocked) {
            $aggregateData = json_decode(json_encode($formRead->getPayload()), true);
            $timeLimitMessage = $aggregateData['timeLimitMessage'] ?? $this->translator->trans('You have already submitted the form, please try again later');
            if ($form->isSubmitted()) {
                $form->addError(new FormError($timeLimitMessage));
            } else {
                $messages[] = [
                    'type' => 'warning',
                    'message' => $timeLimitMessage,
                ];
            }
        }

        if (!$ignore_validation && !$isBlocked && $form->isSubmitted()) {
            // Check if the form is valid.
            #$submittedData = $form->getData();
            $submittedData = array_map(function ($field) {
                /** @var FormInterface $field */
                return $field->getData();
            }, $form->all());
            $data = array_merge($defaultData, $submittedData);
            $isValid = $form->isValid() && $this->onValidate($form,$formRead, $data);

            if ($isValid && $this->onSubmit($form,$formRead, $data)) {
                $aggregateData = $formRead->getPayload();

                // Build and send the email.
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
                if ($replyTo && $name && \is_string($name)) {
                    $replyToName = $name;
                    if ($firstname && \is_string($firstname)) {
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

                // Get the message body.
                [$body, $contentType] = $this->renderEmailTemplate($formRead->getEmailTemplate(), $formRead->getHtml(), $data);
                $message->setBody($body, $contentType);

                // Send different emails to main recipient and copy recipients.
                if (!empty($formRead->getEmailTemplateCopy())) {
                    // Send copies with different body.
                    $messageCc = clone $message;
                    [$body, $contentType] = $this->renderEmailTemplate($formRead->getEmailTemplateCopy(), $formRead->getHtml(), $data);
                    $messageCc->setBody($body, $contentType);
                    $messageCc->setTo(null);
                    $this->mailer->send($messageCc);

                    // Send to main recipient.
                    $message->setCc(null);
                    $message->setBcc(null);
                    $this->mailer->send($message);
                } else {
                    // Send to all recipients with same message body.
                    $this->mailer->send($message);
                }

                // Display Success Message.
                $messages[] = [
                    'type' => 'success',
                    'message' => $formRead->getSuccessText(),
                ];

                // Save submission in submission table.
                $timelimit = $aggregateData['timelimit'] ?? 0;
                if ($timelimit || $formRead->getSaveSubmissions()) {
                    $this->saveFormSubmission((int) $timelimit, $ip, $formRead, $data);
                }
            }
        }

        return [
            'ignore_validation' => $ignore_validation,
            'formView' => $form->createView(),
            'messages' => $messages,
        ];
    }

    private function onValidate($form, FormRead $formRead, array $data): bool
    {
        $aggregateData = $formRead->getPayload();

        // Execute onValidate listeners.
        foreach ($aggregateData['items'] as $item) {
            $itemClass = $this->getItemClass($item['itemName']);
            // Get the form as a service or instantiate it.
            try {
                $itemForm = $this->container->get($itemClass);
            } catch (ServiceNotFoundException $exception) {
                $itemForm = new $itemClass();
            }

            if ($itemForm instanceof ItemInterface && !$itemForm->onValidate($data, $item['data'], $formRead, $form)) {
                break;
            }
        }

        return true;
    }

    private function onSubmit($form, FormRead $formRead, array $data): bool
    {
        $aggregateData = $formRead->getPayload();

        // Execute onSubmit listeners.
        foreach ($aggregateData['items'] as $item) {
            $itemClass = $this->getItemClass($item['itemName']);
            // Get the form as a service or instantiate it.
            try {
                $itemForm = $this->container->get($itemClass);
            } catch (ServiceNotFoundException $exception) {
                $itemForm = new $itemClass();
            }

            if ($itemForm instanceof ItemInterface && !$itemForm->onSubmit($data, $item['data'], $formRead, $form)) {
                return false;
            }
        }

        return true;
    }

    private function saveFormSubmission(int $timelimit, string $ip, FormRead $formRead, array $submittedData): void
    {
        $expiresTimestamp = time() + $timelimit;
        $expires = new \DateTime();
        $expires->setTimestamp($expiresTimestamp);
        $formSubmission = new FormSubmission($formRead, $ip, $expires, $submittedData);
        $this->entityManager->persist($formSubmission);
        $this->entityManager->flush();
    }

    private function renderEmailTemplate(string $emailTemplate, bool $isHtml, $data): ?array
    {
        // Try to render the twig template.
        $body = null;
        $error = null;
        try {
            $view = $this->twig->createTemplate($emailTemplate);
            try {
                $body = $view->render($data);
            } catch (\Twig_Error_Runtime $error) {
                $body = null;
            } catch (\Throwable $error) {
                $body = null;
            }
        } catch (\Twig_Error_Syntax $error) {
            $body = null;
        } catch (\Twig_Error_Loader $error) {
            $body = null;
        }

        // If rendering the twig template fails json_encode the raw form data and send as plain text with error attached.
        if (null === $body && \is_object($error) && method_exists($error, 'getRawMessage')) {
            $body = 'An Error occurred: '.$error->getRawMessage()."\nPlease check your Email-Template at line ".$error->getTemplateLine().". \nHere is the raw form submission:";
            $body .= "\n\n".json_encode($data);
            $isHtml = false;
        }

        $contentType = $isHtml ? 'text/html' : 'text/plain';

        return [
            $body,
            $contentType,
        ];
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
        if ($payload['items'] && \is_array($payload['items']) && \count($payload['items']) > 0) {
            foreach ($payload['items'] as $item) {
                if (isset($item['data'][$propertyName]) && $item['data'][$propertyName]) {
                    $isField = $item['data']['name'];
                }
            }
        }

        return $isField ? $data[$isField] : null;
    }

    public function getFormSubmissions(int $id)
    {
        /** @var FormSubmission[] $formSubmissions */
        $formSubmissions = $this->entityManager->getRepository(FormSubmission::class)->findBy([
            'form' => $id,
        ]);

        $payloads = array_map(function ($formSubmission) {
            /** @var FormSubmission $formSubmission */
            return $formSubmission->getPayload();
        }, $formSubmissions);

        return $payloads;
    }
}
