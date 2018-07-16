<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\Forms\Command\FormDeleteCommand;
use RevisionTen\Forms\Listener\FormDeleteListener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

class FormDeleteEvent extends Event implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return FormDeleteCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getListenerClass(): string
    {
        return FormDeleteListener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Form deleted';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_OK;
    }
}
