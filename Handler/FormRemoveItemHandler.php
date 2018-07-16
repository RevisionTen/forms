<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\Forms\Command\FormRemoveItemCommand;
use RevisionTen\Forms\Event\FormRemoveItemEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;

final class FormRemoveItemHandler extends FormBaseHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Form $aggregate
     */
    public function execute(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $command->getPayload();

        $uuid = $payload['uuid'];

        // A function that removes a item from its parent.
        $removeAndRebase = function (&$collection, $uuid) {
            // Remove the item by filtering the items array.
            $collection = array_filter($collection, function ($item, $key) use ($uuid) {
                return $uuid !== $item['uuid'];
            }, ARRAY_FILTER_USE_BOTH);

            // Rebase array values.
            $collection = array_values($collection);
        };

        // Remove from root.
        $removeAndRebase($aggregate->items, $uuid);

        // Remove from children.
        $removeItemFunction = function (&$item, &$collection) use ($removeAndRebase) {
            $removeAndRebase($collection, $item['uuid']);
        };
        self::onItem($aggregate, $uuid, $removeItemFunction);

        return $aggregate;
    }

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
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormRemoveItemEvent($command);
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
        $uuid = $payload['uuid'];
        $item = self::getItem($aggregate, $uuid);

        if (!isset($uuid)) {
            $this->messageBus->dispatch(new Message(
                'No uuid to remove is set',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        } elseif (!$item) {
            $this->messageBus->dispatch(new Message(
                'Item with this uuid was not found',
                CODE_CONFLICT,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        } else {
            return true;
        }
    }
}
