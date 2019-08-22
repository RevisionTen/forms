<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Command;

use RevisionTen\Forms\Handler\FormCreateHandler;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

class FormCreateCommand extends Command implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getHandlerClass(): string
    {
        return FormCreateHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAggregateClass(): string
    {
        return Form::class;
    }
}
