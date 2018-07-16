<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\Forms\Command\FormEditCommand;
use RevisionTen\Forms\Listener\FormEditListener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

class FormEditEvent extends Event implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return FormEditCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getListenerClass(): string
    {
        return FormEditListener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Form edited';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_CREATED;
    }
}
