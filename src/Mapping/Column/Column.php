<?php

namespace Michel\PaperORM\Mapping\Column;

use Michel\PaperORM\Mapping\Index;
use Michel\PaperORM\Schema\SchemaInterface;
use Michel\PaperORM\Tools\NamingStrategy;
use Michel\PaperORM\Types\StringType;
use Michel\PaperORM\Types\Type;
use Michel\PaperORM\Types\TypeFactory;

abstract class Column
{
    private string $property;
    private ?string $name;
    private string $type;
    private bool $unique;
    private bool $nullable;
    private $defaultValue;
    private ?string $firstArgument;
    private ?string $secondArgument;

     public function __construct(
          string $property,
          ?string $name = null,
          string $type = StringType::class,
          bool $nullable = false,
          $defaultValue = null,
          bool $unique = false,
         ?string $firstArgument = null,
         ?string $secondArgument = null
     )
    {

        if (empty($property) && !empty($name)) {
            $property = $name;
        }

        if (!empty($name) && !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid column name "%s": only alphanumeric characters and underscores are allowed.',
                $name
            ));
        }

        $this->property = $property;
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->unique = $unique;
        $this->nullable = $nullable;
        $this->firstArgument = $firstArgument;
        $this->secondArgument = $secondArgument;
    }

    final public function __toString(): string
    {
        return $this->getProperty();
    }

    public function bindProperty(string $propertyName): self
    {
        $this->property = $propertyName;
        return $this;
    }

    public function getProperty(): string
    {
        if (empty($this->property)) {
            throw  new \LogicException('Property must be set, use bindProperty');
        }
        return $this->property;
    }

    final public function getName(): ?string
    {
        $property = $this->getProperty();
        return $this->name ?:  NamingStrategy::toSnakeCase($property);
    }


    public function getType(): string
    {
        return $this->type;
    }

    final public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    final public function getFirstArgument(): ?string
    {
        return $this->firstArgument;
    }

    final public function getSecondArgument(): ?string
    {
        return $this->secondArgument;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }


    /**
     * Converts a value to its corresponding database representation.
     *
     * @param mixed $value The value to be converted.
     * @return mixed The converted value.
     * @throws \ReflectionException
     */
    final function convertToDatabase($value, SchemaInterface $schema)
    {
        $type = $this->getType();
        if (is_subclass_of($type, Type::class)) {
            $value = TypeFactory::create($schema, $type)->convertToDatabase($value);
        }
        return $value;
    }

    /**
     * Converts a value to its corresponding PHP representation.
     *
     * @param mixed $value The value to be converted.
     * @return mixed The converted PHP value.
     * @throws \ReflectionException
     */
    final function convertToPHP($value, SchemaInterface $schema)
    {
        $type = $this->getType();
        if (is_subclass_of($type, Type::class)) {
            $value = TypeFactory::create($schema, $type)->convertToPHP($value);
        }
        return $value;
    }
}
