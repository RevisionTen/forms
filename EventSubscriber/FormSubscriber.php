<?php

declare(strict_types=1);

namespace RevisionTen\Forms\EventSubscriber;

use RevisionTen\CQRS\Event\AggregateUpdatedEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\Forms\Services\FormService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FormSubscriber implements EventSubscriberInterface
{
    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AggregateUpdatedEvent::class => 'updateReadModel',
        ];
    }

    /**
     * @throws \Exception
     */
    public function updateReadModel(AggregateUpdatedEvent $aggregateUpdatedEvent): void
    {
        $event = $aggregateUpdatedEvent->getEvent();

        $aggregateClass = $event::getAggregateClass();
        $aggregateUuid = $event->getAggregateUuid();

        if ($aggregateClass === Form::class) {
            $this->formService->updateFormRead($aggregateUuid);
        }
    }
}
