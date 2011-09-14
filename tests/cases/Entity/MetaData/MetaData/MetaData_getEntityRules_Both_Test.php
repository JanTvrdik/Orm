<?php

use Orm\MetaData;
use Orm\RepositoryContainer;

/**
 * @covers Orm\MetaData::getEntityRules
 * @covers Orm\MetaData::createEntityRules
 */
class MetaData_getEntityRules_Both_Test extends TestCase
{
	private $m;
	private $m2;
	protected function setUp()
	{
		MetaData::clean();
		MetaData_Test_Entity::$metaData = NULL;
		$this->m = new RepositoryContainer;
		$this->m2 = new RepositoryContainer;
	}

	public function testMoreRepoCon()
	{
		MetaData_Test_Entity::$count = 0;
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m);
		$this->assertSame(1, MetaData_Test_Entity::$count);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m2);
		$this->assertSame(2, MetaData_Test_Entity::$count);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m2);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m);
		$this->assertSame(2, MetaData_Test_Entity::$count);
	}

	public function testNoRepoConCache()
	{
		MetaData_Test_Entity::$count = 0;
		MetaData::getEntityRules('MetaData_Test_Entity');
		MetaData::getEntityRules('MetaData_Test_Entity');
		$this->assertSame(1, MetaData_Test_Entity::$count);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m);
		$this->assertSame(1, MetaData_Test_Entity::$count);
		MetaData::getEntityRules('MetaData_Test_Entity');
		$this->assertSame(1, MetaData_Test_Entity::$count);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m);
		$this->assertSame(1, MetaData_Test_Entity::$count);
	}

	public function testNoRepoConCache_OnlyFirst()
	{
		MetaData_Test_Entity::$count = 0;
		MetaData::getEntityRules('MetaData_Test_Entity');
		$this->assertSame(1, MetaData_Test_Entity::$count);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m);
		$this->assertSame(1, MetaData_Test_Entity::$count);
		MetaData::getEntityRules('MetaData_Test_Entity', $this->m2);
		$this->assertSame(2, MetaData_Test_Entity::$count);
	}

	public function testReflection()
	{
		$r = new ReflectionMethod('Orm\MetaData', 'getEntityRules');
		$this->assertTrue($r->isPublic(), 'visibility');
		$this->assertFalse($r->isFinal(), 'final');
		$this->assertTrue($r->isStatic(), 'static');
		$this->assertFalse($r->isAbstract(), 'abstract');
	}

}
