<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use DateTimeImmutable;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\Forms\Event\FormCloneEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class FormCloneHandler extends FormBaseHandler implements HandlerInterface
{
    /** @var AggregateFactory */
    private $aggregateFactory;

    /**
     * PageCloneHandler constructor.
     *
     * @param \RevisionTen\CQRS\Services\AggregateFactory $aggregateFactory
     */
    public function __construct(AggregateFactory $aggregateFactory)
    {
        $this->aggregateFactory = $aggregateFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        $originalUuid = $payload['originalUuid'];
        $originalVersion = $payload['originalVersion'];

        if ($this->aggregateFactory) {
            // Build original aggregate and use its state as a starting point.
            /** @var Form $originalAggregate */
            $originalAggregate = $this->aggregateFactory->build($originalUuid, Form::class, (int) $originalVersion);

            // Override title.
            $originalAggregate->title .= ' duplicate';

            // Override aggregate meta info.
            $originalAggregate->setUuid($aggregate->getUuid());
            $originalAggregate->setVersion($aggregate->getVersion() ?? 1);
            $originalAggregate->setStreamVersion($aggregate->getStreamVersion() ?? 1);
            $originalAggregate->setSnapshotVersion(null);
            $originalAggregate->setCreated(new DateTimeImmutable());
            $originalAggregate->setModified(new DateTimeImmutable());
            $originalAggregate->setHistory([]);

            $aggregate = $originalAggregate;
        }

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormCloneEvent(
            $command->getAggregateUuid(),
            $command->getUuid(),
            $command->getOnVersion() + 1,
            $command->getUser(),
            $command->getPayload()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        $payload = $command->getPayload();

        if (0 !== $aggregate->getVersion()) {
            throw new CommandValidationException(
                'Aggregate already exists',
                CODE_CONFLICT,
                NULL,
                $command
            );
        }

        if (empty($payload['originalUuid']) || empty($payload['originalVersion'])) {
            throw new CommandValidationException(
                'You must provide an original uuid and version',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        return true;
    }
}
