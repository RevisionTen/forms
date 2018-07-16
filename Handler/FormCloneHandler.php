<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\Forms\Command\FormCloneCommand;
use RevisionTen\Forms\Event\FormCloneEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;

final class FormCloneHandler extends FormBaseHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Form $aggregate
     */
    public function execute(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $command->getPayload();

        $originalUuid = $payload['originalUuid'];
        $originalVersion = $payload['originalVersion'];

        if ($this->aggregateFactory) {
            // Build original aggregate and use its state as a starting point.
            /** @var Form $originalAggregate */
            $originalAggregate = $this->aggregateFactory->build($originalUuid, Form::class, intval($originalVersion));

            // Override title.
            $originalAggregate->title = $originalAggregate->title.' duplicate';

            // Override aggregate meta info.
            $originalAggregate->setUuid($aggregate->getUuid());
            $originalAggregate->setVersion($aggregate->getVersion() ?? 1);
            $originalAggregate->setStreamVersion($aggregate->getStreamVersion() ?? 1);
            $originalAggregate->setSnapshotVersion(null);
            $originalAggregate->setCreated(new \DateTimeImmutable());
            $originalAggregate->setModified(new \DateTimeImmutable());
            $originalAggregate->setHistory([]);

            $aggregate = $originalAggregate;
        }

        return $aggregate;
    }

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
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormCloneEvent($command);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        $payload = $command->getPayload();

        if (
            0 === $aggregate->getVersion() &&
            isset($payload['originalUuid']) &&
            !empty($payload['originalUuid']) &&
            isset($payload['originalVersion']) &&
            !empty($payload['originalVersion'])
        ) {
            return true;
        }

        if (0 !== $aggregate->getVersion()) {
            $this->messageBus->dispatch(new Message(
                'Aggregate already exists',
                CODE_CONFLICT,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        } else {
            $this->messageBus->dispatch(new Message(
                'You must provide an original uuid and version',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }
    }
}
