<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\Forms\Event\FormEditItemEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use function is_string;

final class FormEditItemHandler extends FormBaseHandler implements HandlerInterface
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

        // Add to items.
        $data = $payload['data'];
        $uuid = $payload['uuid'];

        // A function that updates the items data by merging it with the new data.
        $updateDataFunction = static function (&$item, &$collection) use ($data) {
            $item['data'] = array_merge($item['data'], $data);
        };
        self::onItem($aggregate, $uuid, $updateDataFunction);

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormEditItemEvent(
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
        // The uuid to edit.
        $uuid = $payload['uuid'] ?? null;
        $item = is_string($uuid) ? self::getItem($aggregate, $uuid) : false;

        if (null === $uuid) {
            throw new CommandValidationException(
                'No uuid to edit is set',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        if (!isset($payload['data'])) {
            throw new CommandValidationException(
                'No data set',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        if (!$item) {
            throw new CommandValidationException(
                'Item with this uuid was not found '.$uuid,
                CODE_CONFLICT,
                NULL,
                $command
            );
        }

        return true;
    }
}
