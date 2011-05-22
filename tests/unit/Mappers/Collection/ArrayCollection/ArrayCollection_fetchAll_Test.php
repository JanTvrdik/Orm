<?php

use Orm\ArrayCollection;

require_once dirname(__FILE__) . '/../../../../boot.php';

/**
 * @covers Orm\ArrayCollection::fetchAll
 */
class ArrayCollection_fetchAll_Test extends ArrayCollection_Base_Test
{

	public function test()
	{
		$this->assertSame($this->e, $this->c->fetchAll());
	}

	public function testEmpty()
	{
		$c = new ArrayCollection(array());
		$this->assertSame(array(), $c->fetchAll());
	}

	public function testEmpty2()
	{
		$this->c->applyLimit(0);
		$this->assertSame(array(), $this->c->fetchAll());
	}

	public function test2()
	{
		$this->c->applyLimit(1, 1);
		$this->assertSame(array($this->e[1]), $this->c->fetchAll());
	}

}
