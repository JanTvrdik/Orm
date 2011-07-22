<?php

/**
 * @covers Orm\_EntityBase::createMetaData
 */
class EntityBase_createMetaData_Test extends TestCase
{

	public function test1()
	{
		$this->assertInstanceOf('Orm\MetaData', TestEntity::createMetaData('TestEntity'));
	}

	public function test2()
	{
		$this->assertSame(array(
			'id' => array(
				'types' => array('id' => 'id'),
				'get' => array('method' => 'getId'),
				'set' => NULL,
				'since' => 'Orm\Entity',
				'relationship' => NULL,
				'relationshipParam' => NULL,
				'default' => NULL,
				'enum' => NULL,
				'injection' => NULL,
			),
			'string' => array(
				'types' => array('string' => 'string'),
				'get' => array('method' => NULL),
				'set' => array('method' => NULL),
				'since' => 'TestEntity',
				'relationship' => NULL,
				'relationshipParam' => NULL,
				'default' => NULL,
				'enum' => NULL,
				'injection' => NULL,
			),
			'date' => array(
				'types' => array('datetime' => 'datetime'),
				'get' => array('method' => NULL),
				'set' => array('method' => NULL),
				'since' => 'TestEntity',
				'relationship' => NULL,
				'relationshipParam' => NULL,
				'default' => NULL,
				'enum' => NULL,
				'injection' => NULL,
			),
		), TestEntity::createMetaData('TestEntity')->toArray());
	}

}