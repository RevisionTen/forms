<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Listener;

use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\ListenerInterface;
use RevisionTen\CQRS\Services\CommandBus;

class FormRemoveItemListener extends FormBaseListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(CommandBus $commandBus, EventInterface $event): void
    {
        // Update the FormRead Model.
        $formUuid = $event->getCommand()->getAggregateUuid();
        $this->formService->updateFormRead($formUuid);
    }
}
