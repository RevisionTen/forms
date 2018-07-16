<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\Forms\Command\FormEditItemCommand;
use RevisionTen\Forms\Listener\FormEditItemListener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

class FormEditItemEvent extends Event implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return FormEditItemCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getListenerClass(): string
    {
        return FormEditItemListener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Item edited';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_OK;
    }
}
