<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\Forms\Command\FormDeleteCommand;
use RevisionTen\Forms\Event\FormDeleteEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class FormDeleteHandler extends FormBaseHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Form $aggregate
     */
    public function execute(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface
    {
        // Change Aggregate state.
        $aggregate->deleted = true;

        return $aggregate;
    }

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
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormDeleteEvent($command);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        return true;
    }
}
