<?php

require_once dirname(__FILE__) . '/../../../../boot.php';

/**
 * @covers Orm\DataSourceCollection::getIterator
 */
class DataSourceCollection_getIterator_Test extends DataSourceCollection_BaseConnected_Test
{

	public function testOk()
	{
		$this->e(3);
		$i = $this->c->getIterator();
		$this->assertInstanceOf('Orm\EntityIterator', $i);
		$a = iterator_to_array($i);
		$this->assertSame(3, count($a));
		$this->assertInstanceOf('TestEntity', $a[0]);
	}

	public function testNoRow()
	{
		$this->e(0);
		$this->assertSame(array(), iterator_to_array($this->c->getIterator()));
	}

}
