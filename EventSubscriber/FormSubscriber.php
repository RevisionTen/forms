<?php

declare(strict_types=1);

namespace RevisionTen\Forms\EventSubscriber;

use RevisionTen\CQRS\Event\AggregateUpdatedEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\Forms\Services\FormService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    /**
     * @var FormService
     */
    protected $formService;

    /**
     * FormBaseListener constructor.
     *
     * @param FormService $formService
     */
    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }

    public static function getSubscribedEvents()
    {
        return [
            AggregateUpdatedEvent::NAME => 'updateReadModel',
        ];
    }

    public function updateReadModel(AggregateUpdatedEvent $aggregateUpdatedEvent): void
    {
        /** @var \RevisionTen\CQRS\Interfaces\EventInterface $event */
        $event = $aggregateUpdatedEvent->getEvent();

        $aggregateClass = $event::getAggregateClass();
        $aggregateUuid = $event->getAggregateUuid();

        if ($aggregateClass === Form::class) {
            $this->formService->updateFormRead($aggregateUuid);
        }
    }
}
