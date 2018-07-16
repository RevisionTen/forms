<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\Forms\Command\FormRemoveItemCommand;
use RevisionTen\Forms\Listener\FormRemoveItemListener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class FormRemoveItemEvent extends Event implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return FormRemoveItemCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getListenerClass(): string
    {
        return FormRemoveItemListener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Item removed from Form';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_OK;
    }
}
