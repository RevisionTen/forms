<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\Forms\Handler\FormAddItemHandler;
use RevisionTen\Forms\Model\Form;

class FormAddItemEvent extends AggregateEvent implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getAggregateClass(): string
    {
        return Form::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getHandlerClass(): string
    {
        return FormAddItemHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Item added to Form';
    }
}
