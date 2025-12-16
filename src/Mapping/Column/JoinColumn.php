<?php

namespace Michel\PaperORM\Mapping\Column;

use Michel\PaperORM\Types\IntegerType;
use ReflectionClass;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class JoinColumn extends Column
{
    public const NO_ACTION   = 0;
    public const RESTRICT    = 1;
    public const CASCADE     = 2;
    public const SET_NULL    = 3;
    public const SET_DEFAULT = 4;

    /**
     * @var string
     */
    private string $referencedColumnName;
    /**
     * @var string
     */
    private string $targetEntity;
    private int $onDelete;
    private int $onUpdate;

    final public function __construct(
        string  $name,
        string  $targetEntity,
        string  $referencedColumnName = 'id',
        bool   $nullable = false,
        bool   $unique = false,
        int    $onDelete = self::NO_ACTION,
        int    $onUpdate = self::NO_ACTION

    )
    {

        if ($onDelete === self::SET_NULL && $nullable === false) {
            throw new \InvalidArgumentException('SET NULL requires nullable=true.');
        }

        parent::__construct('', $name, IntegerType::class, $nullable, null, $unique);
        $this->referencedColumnName = $referencedColumnName;
        $this->targetEntity = $targetEntity;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
    }

    public function getReferencedColumnName(): string
    {
        return $this->referencedColumnName;
    }

    /**
     * @return class-string
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }


    public function getType(): string
    {
        return '\\' . ltrim(parent::getType(), '\\');
    }

    public function getOnDelete(): int
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): int
    {
        return $this->onUpdate;
    }
}
