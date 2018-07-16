<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\Forms\Command\FormCloneCommand;
use RevisionTen\Forms\Listener\FormCloneListener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

class FormCloneEvent extends Event implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return FormCloneCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getListenerClass(): string
    {
        return FormCloneListener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Form duplicated';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_CREATED;
    }
}
