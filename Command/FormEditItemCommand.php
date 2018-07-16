<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Command;

use RevisionTen\Forms\Handler\FormEditItemHandler;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

class FormEditItemCommand extends Command implements CommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function getHandlerClass(): string
    {
        return FormEditItemHandler::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregateClass(): string
    {
        return Form::class;
    }
}
