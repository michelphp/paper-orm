<?php

namespace Michel\PaperORM\Metadata;

use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapper\EntityMapper;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Metadata\ForeignKeyMetadata;

class ColumnMetadata
{
    private string $name;
    private string $type;
    private bool $isPrimary;
    private ?ForeignKeyMetadata $foreignKeyMetadata = null;
    private bool $isNullable;
    private $defaultValue;
    private ?string $comment;
    private array $attributes;
    private ?IndexMetadata $indexMetadata;

    public function __construct(
        string              $name,
        string              $type,
        bool                $isPrimary = false,
        bool                $isNullable = true,
                            $defaultValue = null,
        ?ForeignKeyMetadata $foreignKeyMetadata = null,
        ?string             $comment = null,
        array               $attributes = []
    )
    {
        $this->name = $name;
        $this->type = strtoupper($type);
        $this->isPrimary = $isPrimary;
        $this->isNullable = $isNullable;
        $this->defaultValue = $defaultValue;
        $this->foreignKeyMetadata = $foreignKeyMetadata;
        $this->comment = $comment;
        $this->attributes = $attributes;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeWithAttributes(): string
    {
        if (!empty($this->attributes)) {
            return sprintf('%s(%s)', $this->getType(), implode(',', $this->attributes));
        }
        return $this->getType();
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function getForeignKeyMetadata(): ?ForeignKeyMetadata
    {
        return $this->foreignKeyMetadata;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function getDefaultValue()
    {
        $value = $this->defaultValue;
        if (is_bool($value)) {
            return intval($value);
        }
        return $value;
    }

    public function getDefaultValuePrintable()
    {
        $value = $this->defaultValue;
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_string($value)) {
            return "'$value'";
        }
        return $value;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function fromColumn(
        Column $column,
        string $sqlType,
        ?ForeignKeyMetadata $foreignKeyMetadata = null,
        ?string $defaultFirstArgument = null,
        ?string $defaultSecondArgument = null
    ): self
    {
        $arguments = [];
        if ($column->getFirstArgument()) {
            $arguments[] = $column->getFirstArgument();
        }elseif ($defaultFirstArgument) {
            $arguments[] = $defaultFirstArgument;
        }
        if ($column->getSecondArgument()) {
            $arguments[] = $column->getSecondArgument();
        }elseif ($defaultSecondArgument) {
            $arguments[] = $defaultSecondArgument;
        }

        $defaultValue = $column->getDefaultValue();
        if (is_array($defaultValue)) {
            $defaultValue = json_encode($defaultValue);
        }
        return new self(
            $column->getName(),
            $sqlType,
            $column instanceof PrimaryKeyColumn,
            $column->isNullable(),
            $defaultValue,
            $foreignKeyMetadata,
            null,
            $arguments
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['type'],
            $data['primary'] ?? false,
            $data['null'] ?? true,
        $data['default'] ?? null,
            $data['foreignKeyMetadata'] ?? null,
        $data['comment'] ?? null,
            $data['attributes'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'primary' => $this->isPrimary(),
            'null' => $this->isNullable(),
            'default' => $this->getDefaultValue(),
            'foreignKeyMetadata' => $this->getForeignKeyMetadata() ? $this->getForeignKeyMetadata()->toArray() : null,
            'comment' => $this->getComment(),
            'attributes' => $this->getAttributes(),
        ];
    }
}
