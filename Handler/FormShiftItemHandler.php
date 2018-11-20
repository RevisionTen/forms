<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Handler;

use RevisionTen\Forms\Command\FormShiftItemCommand;
use RevisionTen\Forms\Event\FormShiftItemEvent;
use RevisionTen\Forms\Model\Form;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use RevisionTen\CQRS\Message\Message;

final class FormShiftItemHandler extends FormBaseHandler implements HandlerInterface
{
    /**
     * Shifts an item in an array one down.
     *
     * @param array $array
     * @param int   $item
     *
     * @return array
     */
    private static function down(array $array, int $item): array
    {
        if (\count($array) - 1 > $item) {
            $b = \array_slice($array, 0, $item, true);
            $b[] = $array[$item + 1];
            $b[] = $array[$item];
            $b += \array_slice($array, $item + 2, \count($array), true);

            return $b;
        }

        return $array;
    }

    /**
     * Shifts an item in an array one up.
     *
     * @param array $array
     * @param int   $item
     *
     * @return array
     */
    private static function up(array $array, int $item): array
    {
        if ($item > 0 && $item < \count($array)) {
            $b = \array_slice($array, 0, $item - 1, true);
            $b[] = $array[$item];
            $b[] = $array[$item - 1];
            $b += \array_slice($array, $item + 1, \count($array), true);

            return $b;
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     *
     * @var Form $aggregate
     */
    public function execute(CommandInterface $command, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $command->getPayload();

        $uuid = $payload['uuid'];
        $direction = $payload['direction'];

        // A function that shifts all matching items in a provided direction.
        $shiftFunction = function (&$item, &$collection) use ($direction, $uuid) {
            if (null !== $collection) {
                // Get the key of the item that will shift.
                $itemKey = null;
                foreach ($collection as $key => $subItem) {
                    if ($subItem['uuid'] === $uuid) {
                        $itemKey = $key;
                        continue;
                    }
                }

                if (null !== $itemKey && 'up' === $direction) {
                    $collection = self::up($collection, $itemKey);
                } elseif (null !== $itemKey && 'down' === $direction) {
                    $collection = self::down($collection, $itemKey);
                }
            }
        };

        self::onItem($aggregate, $uuid, $shiftFunction);

        return $aggregate;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCommandClass(): string
    {
        return FormShiftItemCommand::class;
    }

    /**
     * {@inheritdoc}
     */
    public function createEvent(CommandInterface $command): EventInterface
    {
        return new FormShiftItemEvent($command);
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
        $item = \is_string($uuid) ? self::getItem($aggregate, $uuid) : false;

        if (null === $uuid) {
            $this->messageBus->dispatch(new Message(
                'No uuid to shift is set',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        if (!$item) {
            $this->messageBus->dispatch(new Message(
                'Item with this uuid was not found '.$uuid,
                CODE_CONFLICT,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        if (!isset($payload['direction']) || ('up' !== $payload['direction'] && 'down' !== $payload['direction'])) {
            $this->messageBus->dispatch(new Message(
                'Shift direction is not set',
                CODE_BAD_REQUEST,
                $command->getUuid(),
                $command->getAggregateUuid()
            ));

            return false;
        }

        return true;
    }
}
