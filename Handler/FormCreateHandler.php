<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\Forms\Command\FormCreateCommand;
use RevisionTen\Forms\Event\FormCreateEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;

final class FormCreateHandler extends FormBaseHandler implements HandlerInterface
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
        return FormCreateCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormCreateEvent($command);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        $payload = $command->getPayload();

        if (0 !== $aggregate->getVersion()) {
            $this->messageBus->dispatch(new Message(
                'Aggregate already exists',
                CODE_CONFLICT,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        if (empty($payload['title'])) {
            $this->messageBus->dispatch(new Message(
                'You must enter a title',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        return true;
    }
}
