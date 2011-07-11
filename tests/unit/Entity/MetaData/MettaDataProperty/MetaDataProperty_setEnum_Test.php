<?php

use Orm\MetaData;
use Orm\MetaDataProperty;

/**
 * @covers Orm\MetaDataProperty::setEnum
 */
class MetaDataProperty_setEnum_Test extends TestCase
{
	private $p;

	protected function setUp()
	{
		$m = new MetaData('MetaData_Test_Entity');
		$this->p = new MetaDataProperty($m, 'id', 'null');
	}

	private function getEnum()
	{
		$a = $this->p->toArray();
		return $a['enum'];
	}

	public function test()
	{
		$this->p->setEnum(array(1,2,3));
		$this->assertSame(array('constants' => array(1,2,3), 'original' => '1, 2, 3'), $this->getEnum());

		$this->p->setEnum(array('xxx'), 'MetaData_Test_Entity::XXX');
		$this->assertSame(array('constants' => array('xxx'), 'original' => 'MetaData_Test_Entity::XXX'), $this->getEnum());
	}

}
