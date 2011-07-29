<?php

use Orm\RepositoryContainer;

/**
 * @covers Orm\ValueEntityFragment::onAttach
 */
class ValueEntityFragment_onAttach_Test extends TestCase
{
	private $r;

	protected function setUp()
	{
		$m = new RepositoryContainer;
		$this->r = $m->testentityrepository;
	}

	public function test()
	{
		$e = new TestEntity;
		$rule = $this->readAttribute($e, 'rules');
		$e->___event($e, 'attach', $this->r);
		$this->assertAttributeSame($rule, 'rules', $e);
	}

	public function testError()
	{
		$e = new ValueEntityFragment_onAttach_Entity;
		$this->setExpectedException('Nette\InvalidStateException', 'fooBar isn\'t repository in ValueEntityFragment_onAttach_Entity::$mixed');
		$e->___event($e, 'attach', $this->r);
	}

}
