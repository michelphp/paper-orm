<?php

namespace Michel\PaperORM\Proxy;

use DateTimeInterface;
use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Column\Column;
use Michel\PaperORM\Mapping\Column\DateColumn;
use Michel\PaperORM\Mapping\Column\DateTimeColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Types\DateTimeType;

trait ProxyInitializedTrait
{
    /**
     * @var array<string,Column>
     */
    private array $__propertiesInitialized = [];
    private array $__valuesInitialized = [];
    private bool $__initialized = false;

    public function __setInitialized(array $propertiesInitialized)
    {
        $this->__initialized = true;
        $this->__propertiesInitialized = $propertiesInitialized;
        $this->__valuesInitialized = $this->getValues();
    }

    public function __isInitialized(): bool
    {
        return $this->__initialized;
    }

    public function __wasModified(): bool
    {
        if (!$this->__initialized) {
            return false;
        }
        return count($this->__getPropertiesModified()) > 0;
    }

    public function __getPropertiesModified() : array
    {
        if (!$this->__initialized) {
            return [];
        }

        $changed = [];
        $initial = $this->__valuesInitialized;
        $current = $this->getValues();

        foreach ($current as $key => $value) {
            if (!array_key_exists($key, $initial)) {
                $changed[] = $key;
                continue;
            }

            if ($value !== $initial[$key]) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    public function __destroy() : void
    {
        $this->__initialized = false;
        $this->__propertiesInitialized = [];
        $this->__valuesInitialized = [];
    }

    public function __reset(): void
    {
        $this->__setInitialized($this->__propertiesInitialized);
    }

    public function __getParentClass(): string
    {
        return get_parent_class($this);
    }

    private function getValues(): array
    {
        $reflectionProxy = new \ReflectionClass($this);
        $reflection = $reflectionProxy->getParentClass();
        $cleanedData = [];

        foreach ($this->__propertiesInitialized as $key => $column) {
            $property = $reflection->getProperty($key);
            $property->setAccessible(true);
            $value = $property->getValue($this);
            if ($column->getType() == DateTimeType::class && $value instanceof DateTimeInterface) {
                $cleanedData[$key] = $value->getTimestamp();
            } elseif ($column instanceof DateColumn && $value instanceof DateTimeInterface) {
                $cleanedData[$key] = $value;
            } elseif ($column instanceof JoinColumn && $value instanceof EntityInterface) {
                $cleanedData[$key] = $value->getPrimaryKeyValue();
            } else {
                $cleanedData[$key] = $value;
            }
            unset($value);
        }
        return $cleanedData;
    }
}
