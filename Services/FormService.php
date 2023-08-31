<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Services;

use DateTime;
use Doctrine\Common\Collections\Criteria;
use Exception;
use ReflectionException;
use ReflectionMethod;
use RevisionTen\Forms\Form\Items\TextItem;
use RevisionTen\Forms\Interfaces\ItemInterface;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Services\AggregateFactory;
use Doctrine\ORM\EntityManagerInterface;
use RevisionTen\Forms\Entity\FormRead;
use RevisionTen\Forms\Entity\FormSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function explode;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function md5;
use function method_exists;
use function preg_replace;
use function strtolower;
use function time;

/**
 * Class FormService.
 */
class FormService
{
    private ContainerInterface $container;

    private TranslatorInterface $translator;

    private MailerInterface $mailer;

    private Environment $twig;

    private EntityManagerInterface $entityManager;

    private AggregateFactory $aggregateFactory;

    private FormFactoryInterface $formFactory;

    private UrlGeneratorInterface $urlGenerator;

    private array $config;

    public function __construct(ContainerInterface $container, TranslatorInterface $translator, MailerInterface $mailer, Environment $twig, EntityManagerInterface $entityManager, AggregateFactory $aggregateFactory, FormFactoryInterface $formFactory, UrlGeneratorInterface $urlGenerator, array $config)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->aggregateFactory = $aggregateFactory;
        $this->formFactory = $formFactory;
        $this->urlGenerator = $urlGenerator;
        $this->config = $config;
    }

    /**
     * Update the FormRead entity for the admin backend.
     *
     * @param string $formUuid
     *
     * @throws Exception
     */
    public function updateFormRead(string $formUuid): void
    {
        /**
         * @var Form $aggregate
         */
        $aggregate = $this->aggregateFactory->build($formUuid, Form::class);

        // Build FormRead entity from Aggregate.
        $formRead = $this->entityManager->getRepository(FormRead::class)->findOneBy(['uuid' => $formUuid]) ?? new FormRead();
        $formRead->setVersion($aggregate->getStreamVersion());
        $formRead->setUuid($formUuid);

        $formData = json_decode(json_encode($aggregate), true, 512);
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
        $formRead->setTrackSubmissions($aggregate->trackSubmissions);
        $formRead->setDisableCsrfProtection($aggregate->disableCsrfProtection);
        $formRead->setScrollToSuccessText($aggregate->scrollToSuccessText);
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
     * @param null $data
     * @param bool $ignore_validation
     *
     * @return FormInterface
     *
     * @throws ReflectionException
     */
    public function getForm(string $formUuid, $data = null, bool $ignore_validation = false): FormInterface
    {
        /**
         * @var FormRead $formRead
         */
        $formRead = $this->entityManager->getRepository(FormRead::class)->findOneBy(['uuid' => $formUuid]);
        $payload = $formRead->getPayload();

        $formName = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $formRead->getTitle()));
        if (empty($formName)) {
            $formName = $formRead->getUuid();
        }

        $formOptions = [
            'action' => '?formular=abgeschickt',
            'csrf_protection' => !$formRead->getDisableCsrfProtection(),
        ];

        if ($ignore_validation) {
            $formOptions['validation_groups'] = false;
        }

        $formBuilder = $this->formFactory->createNamedBuilder($formName, FormType::class, $data, $formOptions);

        foreach ($payload['items'] as $item) {
            $itemClass = $this->getItemClass($item['itemName']);
            // Get the form as a service or instantiate it.
            try {
                /**
                 * @var object $itemForm
                 */
                $itemForm = $this->container->get($itemClass);
            } catch (ServiceNotFoundException $exception) {
                $itemForm = new $itemClass();
            }

            // Check if the class implements the buildItem method, use fallback getItem method otherwise.
            $reflector = new ReflectionMethod($itemForm, 'buildItem');
            $isProto = ($reflector->getDeclaringClass()->getName() !== get_class($itemForm));

            if ($isProto && method_exists($itemForm, 'getItem')) {
                // Class does not implement buildItem() itself, call deprecated getItem instead.
                $itemForm::getItem($formBuilder, $item['data']);
            } elseif (method_exists($itemForm, 'buildItem')) {
                $itemForm->buildItem($formBuilder, $item['data']);
            }
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
         * @var FormSubmission $formSubmission
         */
        $formSubmission = $this->entityManager->getRepository(FormSubmission::class)->findOneBy([
            'ip' => $ip,
            'form' => $formId,
        ], [
            'created' => Criteria::DESC,
        ]);

        return null !== $formSubmission && time() < $formSubmission->getExpires()->getTimestamp();
    }

    private function addTrackingPixel(Request $request, Email $message, string $trackingToken): Email
    {
        // Get context.
        $context = new RequestContext();
        $context = $context->fromRequest($request);

        // Generate tracking pixel url.
        $this->urlGenerator->setContext($context);
        $trackingUrl = $this->urlGenerator->generate('forms_tracking_pixel', [
            'trackingToken' => $trackingToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Append tracking pixel and always send mail as html.
        $body = $message->getHtmlBody();
        if (empty($body)) {
            $body = '<pre>'.$message->getTextBody().'</pre>';
        }
        $body .= '<br/><img alt="" src="'.$trackingUrl.'">';
        $message->html($body, 'text/html');

        return $message;
    }

    /**
     * @param Request $request
     * @param string $formUuid
     * @param array $defaultData
     * @param bool|null $submit
     *
     * @return array
     *
     * @throws Exception
     */
    public function handleRequest(Request $request, string $formUuid, array $defaultData, ?bool $submit = true): array
    {
        $messages = [];

        /**
         * @var FormRead $formRead
         */
        $formRead = $this->entityManager->getRepository(FormRead::class)->findOneBy(['uuid' => $formUuid]);
        $aggregateData = $formRead->getPayload();

        $ignore_validation = null !== $request && $request->get('ignore_validation');

        $form = $this->getForm($formUuid, $defaultData, $ignore_validation);

        $form->handleRequest($request);

        if ($ignore_validation === false) {
            // Check If user is blocked.
            $timeLimit = (int) ($aggregateData['timelimit'] ?? 0);
            $ip = $request->getClientIp();
            $isBlocked = $ip && $timeLimit && $this->isBlocked($ip, $formRead->getId());

            // Display error If user is blocked.
            if ($isBlocked) {
                $timeLimitMessage = (string) ($aggregateData['timeLimitMessage'] ?? $this->translator->trans('forms.label.timeLimitError', [], 'cms'));
                if ($form->isSubmitted()) {
                    $form->addError(new FormError($timeLimitMessage));
                } else {
                    $messages[] = [
                        'type' => 'warning',
                        'message' => $timeLimitMessage,
                    ];
                }
            }

            // Handle submitted form.
            if ($isBlocked === false && $form->isSubmitted()) {
                // Get the full submitted form data.
                $submittedData = array_map(static function ($field) {
                    return $field->getData();
                }, $form->all());
                $data = array_merge($defaultData, $submittedData);

                // Check if the form is valid.
                $isValid = $form->isValid() && $this->onValidate($form, $formRead, $data);

                if ($submit && $isValid && $this->onSubmit($form, $formRead, $data)) {

                    // Build and send the email.
                    $message = new Email();

                    // Get To Email.
                    $to = $this->getField($aggregateData, $data, 'isReceiver') ?? $formRead->getEmail();
                    $message->to(...self::getMailsFromString($to));

                    // Get CC Email.
                    $cc = $formRead->getEmailCC();

                    // Get BCC Email.
                    $bcc = $formRead->getEmailBCC();

                    // Get ReplyTo Email.
                    $replyTo = $this->getField($aggregateData, $data, 'replyTo');

                    // Get ReplyTo Name.
                    $replyToName = '';
                    $name = $this->getField($aggregateData, $data, 'isName');
                    $firstname = $this->getField($aggregateData, $data, 'isFirstname');
                    if ($replyTo && $name && is_string($name)) {
                        $replyToName = $name;
                        if ($firstname && is_string($firstname)) {
                            $replyToName = $firstname.' '.$replyToName;
                        }
                    }

                    // Set Reply To.
                    if ($replyTo) {
                        $replyToAddress = new Address($replyTo, $replyToName);
                        $message->replyTo($replyToAddress);
                    }

                    // Set Sender and From.
                    $sender = new Address($formRead->getSender(), $replyToName);
                    $message->sender($sender);
                    $message->from($sender);

                    // Get Subject.
                    $message->subject($this->getField($aggregateData, $data, 'isSubject') ?? $formRead->getTitle());

                    // Generate tracking token.
                    $trackingToken = md5(random_bytes(10));

                    // Get the message body.
                    $isHtml = $formRead->getHtml();
                    $body = $this->renderTwigTemplate($formRead->getEmailTemplate(), $data);
                    if ($isHtml) {
                        $message->html($body);
                    } else {
                        $message->text($body);
                    }


                    // Send different emails to main recipient and copy recipients.
                    if (($cc || $bcc) && !empty($formRead->getEmailTemplateCopy())) {
                        $messageCc = clone $message;

                        // Use cc email template.
                        $body = $this->renderTwigTemplate($formRead->getEmailTemplateCopy(), $data);
                        if ($isHtml) {
                            $messageCc->html($body);
                        } else {
                            $messageCc->text($body);
                        }

                        // Send the CC message with different template only to (b)cc recipients.
                        $messageCc->to($sender);
                        $messageCc->cc(...$messageCc->getCc());
                        $messageCc->bcc(...$messageCc->getCc());
                        $this->mailer->send($messageCc);
                    } else {
                        if ($cc) {
                            $message->cc(...self::getMailsFromString($cc));
                        }
                        if ($bcc) {
                            $message->bcc(...self::getMailsFromString($bcc));
                        }
                    }

                    // Send the email with default template.
                    if ($formRead->getTrackSubmissions()) {
                        // Add tracking pixel.
                        $message = $this->addTrackingPixel($request, $message, $trackingToken);
                    }
                    $this->mailer->send($message);

                    // Save submission in submission table.
                    if ($timeLimit || $formRead->getSaveSubmissions()) {
                        $this->saveFormSubmission(
                            $timeLimit,
                            $ip,
                            $formRead,
                            $data,
                            $to,
                            $cc,
                            $bcc,
                            $trackingToken
                        );
                    }

                    // Display Success Message.
                    $successTemplate = $formRead->getSuccessText();
                    $successHtml = $this->renderTwigTemplate($successTemplate, $data);
                    $messages[] = [
                        'type' => 'success',
                        'message' => $successHtml,
                    ];
                }
            }
        }

        // Check if the form is invalid or reloaded.
        // If so, set the _invalidForm attribute in the request object.
        // This attribute can be read in a ResponseListener, to determine
        // if the status code should be set to 422 in order to support Turbo forms.
        // @see https://turbo.hotwired.dev/handbook/drive#redirecting-after-a-form-submission
        if ($form->isSubmitted() && ($ignore_validation || !$form->isValid())) {
            $request->attributes->set('_invalidForm', true);
        }

        return [
            'template' => $formRead->getTemplate(),
            'ignore_validation' => $ignore_validation,
            'form' => $form,
            'formView' => $form->createView(),
            'messages' => $messages,
            'scrollToSuccessText' => (bool) $formRead->getScrollToSuccessText(),
        ];
    }

    /**
     * Execute all custom validation functions of the form items.
     * Returns true if all validations succeed.
     *
     * @param $form
     * @param FormRead $formRead
     * @param array $data
     * @return bool
     */
    private function onValidate($form, FormRead $formRead, array &$data): bool
    {
        $aggregateData = $formRead->getPayload();

        $isValid = true;

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
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Execute all custom submit functions of the form items.
     * Returns true if all submit handlers succeed.
     *
     * @param $form
     * @param FormRead $formRead
     * @param array $data
     * @return bool
     */
    private function onSubmit($form, FormRead $formRead, array &$data): bool
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

    /**
     * @param int $timelimit
     * @param string $ip
     * @param FormRead $formRead
     * @param array $submittedData
     * @param string $to
     * @param string|null $cc
     * @param string|null $bcc
     * @param string $trackingToken
     * @throws Exception
     */
    private function saveFormSubmission(int $timelimit, string $ip, FormRead $formRead, array $submittedData, string $to, ?string $cc, ?string $bcc, string $trackingToken): void
    {
        $expiresTimestamp = time() + $timelimit;
        $expires = new DateTime();
        $expires->setTimestamp($expiresTimestamp);

        $formSubmission = new FormSubmission();
        $formSubmission->setToEmail($to);
        $formSubmission->setCcEmail($cc);
        $formSubmission->setBccEmail($bcc);
        $formSubmission->setForm($formRead);
        $formSubmission->setIp($ip);
        $formSubmission->setExpires($expires);
        $formSubmission->setPayload($submittedData);

        if ($formRead->getTrackSubmissions()) {
            $formSubmission->setTrackingToken($trackingToken);
            $formSubmission->setOpened(false);
        }

        $this->entityManager->persist($formSubmission);
        $this->entityManager->flush();
    }

    private function renderTwigTemplate(string $templateString, $data): string
    {
        // Try to render the twig template.
        try {
            $view = $this->twig->createTemplate($templateString);
            $body = $view->render($data);
        } catch (SyntaxError | Throwable $error) {
            $body = '';
            // If rendering the twig template fails json_encode the raw form data and send as plain text with error attached.
            if (method_exists($error, 'getRawMessage')) {
                $body = 'An Error occurred: '.$error->getRawMessage()."\nPlease check your Template at line ".$error->getTemplateLine().". \nHere is the raw form submission:";
                $body .= "\n\n". json_encode($data, JSON_THROW_ON_ERROR);
            }
        }

        return $body;
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
     * @throws Exception
     */
    private function getField(array $payload, array $data, string $propertyName)
    {
        $payload = json_decode(json_encode($payload), true, 512);
        $fieldData = null;

        if ($payload['items'] && is_array($payload['items']) && count($payload['items']) > 0) {
            foreach ($payload['items'] as $item) {
                if (isset($item['data'][$propertyName]) && $item['data'][$propertyName]) {
                    $itemName = $item['itemName'] ?? null;
                    $name = $item['data']['name'];

                    $fieldData = $data[$name] ?? null;

                    if ($itemName && ('isReceiver' === $propertyName || 'replyTo' === $propertyName)) {
                        // Execute getEmail callback on form field data.
                        $itemClass = $this->getItemClass($itemName);
                        if (method_exists($itemClass, 'getEmail')) {
                            $fieldData = $itemClass::getEmail($fieldData);
                        }
                    }
                }
            }
        }

        return $fieldData;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getFormSubmissions(int $id): array
    {
        /**
         * @var FormSubmission[] $formSubmissions
         */
        $formSubmissions = $this->entityManager->getRepository(FormSubmission::class)->findBy([
            'form' => $id,
        ]);

        return array_map(static function ($formSubmission) {
            return $formSubmission->getPayload();
        }, $formSubmissions);
    }
}
