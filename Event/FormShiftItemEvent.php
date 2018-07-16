<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Event;

use RevisionTen\Forms\Command\FormShiftItemCommand;
use RevisionTen\Forms\Listener\FormShiftItemListener;
use RevisionTen\CQRS\Event\Event;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class FormShiftItemEvent extends Event implements EventInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return FormShiftItemCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getListenerClass(): string
    {
        return FormShiftItemListener::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage(): string
    {
        return 'Item reordered';
    }

    /**
     * {@inheritdoc}
     */
    public static function getCode(): int
    {
        return CODE_OK;
    }
}
