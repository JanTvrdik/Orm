<?php

require_once dirname(__FILE__) . '/../../../../boot.php';

/**
 * @covers Orm\ArrayCollection::toArrayCollection
 */
class ArrayCollection_toArrayCollection_Test extends ArrayCollection_Base_Test
{

	public function test()
	{
		$c = $this->c->toArrayCollection();
		$this->assertInstanceOf('Orm\ArrayCollection', $c);
		$this->assertSame('Orm\ArrayCollection', get_class($c));
		$this->assertNotSame($this->c, $c);
		$this->assertAttributeSame($this->c->getResult(), 'source', $c);
		$this->assertAttributeSame(NULL, 'result', $c);
	}

	public function testSubClass()
	{
		$cOrigin = new ArrayCollection_ArrayCollection($this->e);
		$c = $cOrigin->toArrayCollection();
		$this->assertInstanceOf('Orm\ArrayCollection', $c);
		$this->assertSame('Orm\ArrayCollection', get_class($c));
		$this->assertNotSame($cOrigin, $c);
		$this->assertAttributeSame($cOrigin->getResult(), 'source', $c);
		$this->assertAttributeSame(NULL, 'result', $c);
	}

}