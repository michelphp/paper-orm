<?php

namespace Michel\PaperORM\Hydrator;

use Michel\PaperORM\Mapper\ColumnMapper;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\OneToMany;
use Michel\PaperORM\Types\Type;

/**
 * What the hydrators need to know about each mapped column of a class, computed once per class
 * instead of once per hydrated entity.
 */
final class HydrationPlan
{
    public const SCALAR = 0;
    public const JOIN = 1;
    public const ONE_TO_MANY = 2;

    /**
     * @var array<string, array<int, array{0: string, 1: int, 2: Column|OneToMany, 3: ?string}>>
     */
    private static array $plans = [];

    /**
     * @return array<int, array{0: string, 1: int, 2: Column|OneToMany, 3: ?string}>
     *         key in the hydrated data, kind, column, Type class used to convert a scalar value (null: kept as is)
     */
    public static function get(string $class): array
    {
        if (isset(self::$plans[$class])) {
            return self::$plans[$class];
        }

        $plan = [];
        foreach (array_merge(ColumnMapper::getColumns($class), ColumnMapper::getOneToManyRelations($class)) as $column) {
            if ($column instanceof OneToMany) {
                $plan[] = [$column->getProperty(), self::ONE_TO_MANY, $column, null];
                continue;
            }
            if ($column instanceof JoinColumn) {
                $plan[] = [$column->getProperty(), self::JOIN, $column, null];
                continue;
            }
            $type = $column->getType();
            $plan[] = [$column->getName(), self::SCALAR, $column, is_subclass_of($type, Type::class) ? $type : null];
        }

        return self::$plans[$class] = $plan;
    }
}
