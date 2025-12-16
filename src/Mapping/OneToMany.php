<?php

namespace Michel\PaperORM\Mapping;

use Attribute;
use LogicException;
use Michel\PaperORM\Collection\ObjectStorage;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class OneToMany
{
    private ?string $property = null;
    private string $targetEntity;
    private ?string $mappedBy;
    private array $criteria;
    private ObjectStorage $storage;

    final public function __construct(string $targetEntity, string $mappedBy = null, array $criteria = [])
    {
        $this->targetEntity = $targetEntity;
        $this->mappedBy = $mappedBy;
        $this->criteria = $criteria;
        $this->storage = new ObjectStorage();
    }

    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    public function getMappedBy(): ?string
    {
        return $this->mappedBy;
    }


    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getDefaultValue(): ObjectStorage
    {
        return clone $this->storage;
    }

    public function getType(): string
    {
        return '\\' . ltrim(get_class($this->getDefaultValue()), '\\');
    }

    public function getName(): string
    {
        return $this->getProperty();
    }

    public function getProperty(): string
    {
        if (empty($this->property)) {
            throw  new \LogicException('Property must be set, use bindProperty');
        }
        return $this->property;
    }

    public function bindProperty(string $propertyName): self
    {
        $this->property = $propertyName;
        return $this;
    }
}
