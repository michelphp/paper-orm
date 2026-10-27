<?php

namespace Test\Michel\PaperORM\Common;

use LogicException;
use Michel\PaperORM\Cache\PropertyReflectionCache;
use Michel\UniTester\TestCase;

// The test class comes first: the test finder only reads the first class of a file.
class PropertyReflectionCacheTest extends TestCase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    protected function execute(): void
    {
        $this->testPropertyOfTheClassItself();
        $this->testPrivatePropertyOfAParentClass();
        $this->testPrivatePropertySeenFromAProxy();
        $this->testSameInstanceIsReturned();
        $this->testUnknownProperty();
    }

    private function testPropertyOfTheClassItself(): void
    {
        $entity = new PropertyReflectionCacheEntity();
        PropertyReflectionCache::get($entity, 'name')->setValue($entity, 'Alice');
        $this->assertStrictEquals('Alice', PropertyReflectionCache::get($entity, 'name')->getValue($entity));
    }

    private function testPrivatePropertyOfAParentClass(): void
    {
        $entity = new PropertyReflectionCacheEntity();
        $property = PropertyReflectionCache::get($entity, 'id');
        $property->setValue($entity, 42);

        $this->assertStrictEquals(PropertyReflectionCacheBase::class, $property->getDeclaringClass()->getName());
        $this->assertStrictEquals(42, $property->getValue($entity));
    }

    private function testPrivatePropertySeenFromAProxy(): void
    {
        $proxy = new PropertyReflectionCacheProxy();
        PropertyReflectionCache::get($proxy, 'name')->setValue($proxy, 'Bob');
        PropertyReflectionCache::get($proxy, 'id')->setValue($proxy, 7);

        $this->assertStrictEquals('Bob', PropertyReflectionCache::get($proxy, 'name')->getValue($proxy));
        $this->assertStrictEquals(7, PropertyReflectionCache::get($proxy, 'id')->getValue($proxy));
    }

    private function testSameInstanceIsReturned(): void
    {
        $this->assertTrue(
            PropertyReflectionCache::get(PropertyReflectionCacheEntity::class, 'name')
            === PropertyReflectionCache::get(new PropertyReflectionCacheEntity(), 'name')
        );
    }

    private function testUnknownProperty(): void
    {
        $this->expectException(LogicException::class, function () {
            PropertyReflectionCache::get(PropertyReflectionCacheEntity::class, 'unknown');
        });
    }
}

class PropertyReflectionCacheBase
{
    private ?int $id = null;
}

class PropertyReflectionCacheEntity extends PropertyReflectionCacheBase
{
    private ?string $name = null;
}

// Same shape as a generated proxy: extends the entity, declares nothing.
class PropertyReflectionCacheProxy extends PropertyReflectionCacheEntity
{
}
