<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\Forms\Handler\FormDeleteHandler;
use RevisionTen\Forms\Model\Form;

class FormDeleteEvent extends AggregateEvent implements EventInterface
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
        return FormDeleteHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Form deleted';
    }
}
