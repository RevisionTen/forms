<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Services;

use RevisionTen\Forms\Form\Items\TextItem;
use RevisionTen\Forms\Model\Form;
use RevisionTen\Forms\Model\FormRead;
use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\EventBus;
use RevisionTen\CQRS\Services\EventStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class FormService.
 */
class FormService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var AggregateFactory
     */
    private $aggregateFactory;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var EventBus
     */
    private $eventBus;

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
     * @param EntityManagerInterface $em
     * @param AggregateFactory       $aggregateFactory
     * @param EventStore             $eventStore
     * @param EventBus               $eventBus
     * @param FormFactoryInterface   $formFactory
     * @param array                  $config
     */
    public function __construct(EntityManagerInterface $em, AggregateFactory $aggregateFactory, EventStore $eventStore, EventBus $eventBus, FormFactoryInterface $formFactory, array $config)
    {
        $this->em = $em;
        $this->aggregateFactory = $aggregateFactory;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
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
        $formRead = $this->em->getRepository(FormRead::class)->findOneByUuid($formUuid) ?? new FormRead();
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
        $formRead->setDeleted($aggregate->deleted);
        $formRead->setHtml($aggregate->html);
        $formRead->setSuccessText($aggregate->successText);
        $formRead->setCreated($aggregate->getCreated());
        $formRead->setModified($aggregate->getModified());

        // Persist FormRead entity.
        $this->em->persist($formRead);
        $this->em->flush();
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
     *
     * @return FormInterface
     */
    public function getForm(string $formUuid, $data = null, bool $ignore_validation = false): FormInterface
    {
        /** @var FormRead $formRead */
        $formRead = $this->em->getRepository(FormRead::class)->findOneByUuid($formUuid);
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

        $form = $formBuilder->getForm();

        return $form;
    }
}
