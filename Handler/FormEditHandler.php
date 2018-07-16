<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\Forms\Command\FormEditCommand;
use RevisionTen\Forms\Event\FormEditEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class FormEditHandler extends FormBaseHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Form $aggregate
     */
    public function execute(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $command->getPayload();

        // Change Aggregate state.
        // Get each public property from the aggregate and update it If a new value exists in the payload.
        $reflect = new \ReflectionObject($aggregate);
        foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            if (array_key_exists($propertyName, $payload)) {
                $aggregate->{$propertyName} = $payload[$propertyName];
            }
        }

        return $aggregate;
    }

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
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormEditEvent($command);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        return true;
    }
}
