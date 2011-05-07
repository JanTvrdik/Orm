<?php

require_once __DIR__ . '/../../../boot.php';

/**
 * @covers ManyToMany::getIterator
 */
class ManyToMany_getIterator_Test extends ManyToMany_Test
{

	public function test()
	{
		$this->assertInstanceOf('Traversable', $this->m2m->getIterator());
	}

	public function test2()
	{
		$this->assertSame($this->m2m->get()->fetchAll(), iterator_to_array($this->m2m->getIterator()));
	}

	public function test3()
	{
		$this->assertSame(4, iterator_count($this->m2m->getIterator()));
	}

}