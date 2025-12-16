<?php

namespace Michel\PaperORM\Assigner;

use Michel\PaperORM\Manager\PaperSequenceManager;
use Michel\PaperORM\Mapping\Column\AutoIncrementColumn;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Tools\IDBuilder;
use Michel\PaperORM\Tools\EntityAccessor;

final class AutoIncrementAssigner implements ValueAssignerInterface, CommitAssignerInterface
{
    private PaperSequenceManager $sequenceManager;

    public function __construct(PaperSequenceManager $sequenceManager)
    {
        $this->sequenceManager = $sequenceManager;
    }

    public function assign(object $entity, Column $column): void
    {
        if (!$column instanceof AutoIncrementColumn) {
            throw new \InvalidArgumentException(sprintf(
                'AutoIncrementAssigner::assign(): expected instance of %s, got %s.',
                AutoIncrementColumn::class,
                get_class($column)
            ));
        }

        $result = self::determineSequenceIdentifiers($column);
        $prefix = $result['sequence'];
        $counter = $this->sequenceManager->peek($result['key']);
        $formatted = sprintf(
            '%s%s',
            $prefix,
            str_pad((string)$counter, $column->getPad(), '0', STR_PAD_LEFT)
        );
        $property = $column->getProperty();
        EntityAccessor::setValue($entity, $property, $formatted);
    }
    public function commit(Column $column): void
    {
        if (!$column instanceof AutoIncrementColumn) {
            throw new \InvalidArgumentException(sprintf(
                'AutoIncrementAssigner::commit(): expected instance of %s, got %s.',
                AutoIncrementColumn::class,
                get_class($column)
            ));
        }

        $this->sequenceManager->increment(self::determineSequenceIdentifiers($column)['key']);
    }

    /**
     * @param AutoIncrementColumn $column
     * @return array{sequence: string, key: string}
     * @throws \RandomException
     */
    private static function determineSequenceIdentifiers(AutoIncrementColumn $column): array
    {
        $key = $column->getKey();

        if (empty($key)) {
            throw new \LogicException(sprintf(
                'AutoIncrementColumn "%s": a non-empty key (sequence or table.sequence) must be defined.',
                $column->getProperty()
            ));
        }

        $prefix = $column->getPrefix();
        $sequenceName = !empty($prefix) ? IDBuilder::generate($prefix) : '';
        if (!empty($sequenceName)) {
            $key = sprintf('%s.%s', $key, $sequenceName);
        }
        return [
            'sequence' => $sequenceName,
            'key' => $key,
        ];
    }

}
