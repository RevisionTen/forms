<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\Forms\Event\FormRemoveItemEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use function is_string;

final class FormRemoveItemHandler extends FormBaseHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Form $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        $uuid = $payload['uuid'];

        // A function that removes a item from its parent.
        $removeAndRebase = static function (&$collection, $uuid) {
            // Remove the item by filtering the items array.
            $collection = array_filter($collection, static function ($item) use ($uuid) {
                return $uuid !== $item['uuid'];
            });

            // Rebase array values.
            $collection = array_values($collection);
        };

        // Remove from root.
        $removeAndRebase($aggregate->items, $uuid);

        // Remove from children.
        $removeItemFunction = static function (&$item, &$collection) use ($removeAndRebase) {
            $removeAndRebase($collection, $item['uuid']);
        };
        self::onItem($aggregate, $uuid, $removeItemFunction);

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormRemoveItemEvent(
            $command->getAggregateUuid(),
            $command->getUuid(),
            $command->getOnVersion() + 1,
            $command->getUser(),
            $command->getPayload()
        );
    }

    /**
     * {@inheritdoc}
     *
     * @var Form $aggregate
     */
    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        $payload = $command->getPayload();
        // The uuid to remove.
        $uuid = $payload['uuid'] ?? null;
        $item = is_string($uuid) ? self::getItem($aggregate, $uuid) : false;

        if (null === $uuid) {
            throw new CommandValidationException(
                'No uuid to remove is set',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        if (!$item) {
            throw new CommandValidationException(
                'Item with this uuid was not found',
                CODE_CONFLICT,
                NULL,
                $command
            );
        }

        return true;
    }
}
