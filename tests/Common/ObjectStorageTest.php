<?php

namespace Test\Michel\PaperORM\Common;


use Michel\PaperORM\Collection\ObjectStorage;
use Michel\UniTester\TestCase;
use Test\Michel\PaperORM\Entity\UserTest;

class ObjectStorageTest extends TestCase
{

    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

    protected function execute(): void
    {
        $this->testFind();
        $this->testFindReturnsNullIfNotFound();
        $this->testFindBy();
        $this->testFindByReturnsEmptyArrayIfNotFound();
        $this->testFirst();
        $this->testFirstReturnsNullIfCollectionIsEmpty();
        $this->testToArray();
        $this->testToArrayReturnsEmptyArrayIfCollectionIsEmpty();
        $this->testIsEmptyReturnsTrueForEmptyObjectStorage();
        $this->testIsEmptyReturnsFalseForObjectStorageWithItems();
        $this->testFindPkWithNullPrimaryKey();
        $this->testFindPkWithNonExistentPrimaryKey();
        $this->testFindPkWithExistingPrimaryKey();
        $this->testFindOneBy();
    }
    public function testFind()
    {
        $collection = new ObjectStorage();
        $collection->attach(new \stdClass());
        $collection->attach(new \stdClass());

        $foundObject = $collection->find(function ($item) {
            return true;
        });

        $this->assertInstanceOf(\stdClass::class, $foundObject);
    }

    public function testFindReturnsNullIfNotFound()
    {
        $collection = new ObjectStorage();

        $foundObject = $collection->find(function ($item) {
            return true;
        });

        $this->assertNull($foundObject);
    }

    public function testFindBy()
    {
        $collection = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $collection->attach($object1);
        $collection->attach($object2);

        $foundObjects = $collection->filter(function ($item) use($object1) {
            return $item === $object1;
        });

        $this->assertStrictEquals(1, count($foundObjects));
        $this->assertTrue(in_array( $object1, $foundObjects));
    }

    public function testFindByReturnsEmptyArrayIfNotFound()
    {
        $collection = new ObjectStorage();

        $foundObjects = $collection->filter(function ($item) {
            return true;
        });

        $this->assertEmpty($foundObjects);
    }

    public function testFirst()
    {
        $collection = new ObjectStorage();
        $object = new \stdClass();
        $collection->attach($object);

        $firstObject = $collection->first();

        $this->assertStrictEquals($object, $firstObject);
    }

    public function testFirstReturnsNullIfCollectionIsEmpty()
    {
        $collection = new ObjectStorage();

        $firstObject = $collection->first();

        $this->assertNull($firstObject);
    }

    public function testToArray()
    {
        $collection = new ObjectStorage();
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $collection->attach($object1);
        $collection->attach($object2);

        $array = $collection->toArray();

        $this->assertStrictEquals(2, count($array));
        $this->assertTrue(in_array( $object1, $array));
        $this->assertTrue(in_array( $object2, $array));
    }

    public function testToArrayReturnsEmptyArrayIfCollectionIsEmpty()
    {
        $collection = new ObjectStorage();

        $array = $collection->toArray();

        $this->assertEmpty($array);
    }

    public function testIsEmptyReturnsTrueForEmptyObjectStorage()
    {
        $objectStorage = new ObjectStorage();
        $this->assertTrue($objectStorage->isEmpty());
    }

    public function testIsEmptyReturnsFalseForObjectStorageWithItems()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();

        $objectStorage = new ObjectStorage();
        $objectStorage->attach($object1);
        $objectStorage->attach($object2);

        $this->assertFalse($objectStorage->isEmpty());
    }

    public function testFindPkWithNullPrimaryKey()
    {
        $collection = new ObjectStorage();
        $result = $collection->findPk(null);
        $this->assertNull($result);
    }

    public function testFindPkWithNonExistentPrimaryKey()
    {
        $collection = new ObjectStorage();
        $result = $collection->findPk(999);
        $this->assertNull($result);
    }

    public function testFindPkWithExistingPrimaryKey()
    {
        $collection = new ObjectStorage();
        $object = new UserTest();
        $object->setId(123);
        $collection->attach($object);
        $result = $collection->findPk(123);
        $this->assertStrictEquals($object, $result);
    }

    public function testFindOneBy()
    {
        $user = new UserTest();
        $user->setFirstname('John');
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($user);
        $foundObject = $objectStorage->findOneByMethod('getFirstname', 'John');
        $this->assertStrictEquals($user, $foundObject);

        $foundObject = $objectStorage->findOneByMethod('getNonExistentMethod', 'John');
        $this->assertNull($foundObject);
    }

}
