<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\Forms\Event\FormAddItemEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;

final class FormAddItemHandler extends FormBaseHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Form $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        if (isset($payload['data']['name'])) {
            // Clean the name to only contain lowercase letters.
            $payload['data']['name'] = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $payload['data']['name']));
        }

        $itemName = $payload['itemName'];
        $data = $payload['data'];

        // Build item data.
        $newItem = [
            'uuid' => $event->getCommandUuid(),
            'itemName' => $itemName,
            'data' => $data,
        ];

        // Add to items.
        $parentUuid = $payload['parent'] ?? null;

        if ($parentUuid && \is_string($parentUuid)) {
            // A function that add the new item to the target parent.
            $addItemFunction = static function (&$item, &$collection) use ($newItem) {
                if (!isset($item['items'])) {
                    $item['items'] = [];
                }
                $item['items'][] = $newItem;
            };
            self::onItem($aggregate, $parentUuid, $addItemFunction);
        } else {
            // Add to Form root.
            $aggregate->items[] = $newItem;
        }

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormAddItemEvent(
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

        if (!isset($payload['itemName'])) {
            $this->messageBus->dispatch(new Message(
                'No item type set',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        if (!isset($payload['data'])) {
            $this->messageBus->dispatch(new Message(
                'No data set',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        return true;
    }
}
