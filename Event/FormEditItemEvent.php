<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\Forms\Handler\FormEditItemHandler;
use RevisionTen\Forms\Model\Form;

class FormEditItemEvent extends AggregateEvent implements EventInterface
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
        return FormEditItemHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Item edited';
    }
}
