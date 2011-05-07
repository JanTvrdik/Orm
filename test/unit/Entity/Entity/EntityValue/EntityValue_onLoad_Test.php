<?php

require_once __DIR__ . '/../../../../boot.php';

/**
 * @covers _EntityValue::onLoad
 */
class EntityValue_onLoad_Test extends TestCase
{
	private $r;

	protected function setUp()
	{
		$m = new Model;
		$this->r = $m->TestEntity;
	}

	public function test()
	{
		$e = $this->r->getById(1);
		$this->assertSame(array('id' => true), $this->readAttribute($e, 'valid'));
		$this->assertInternalType('array', $this->readAttribute($e, 'values'));
		$this->assertSame(false, $e->isChanged());
		$this->assertInternalType('array', $this->readAttribute($e, 'rules'));
	}

	public function test2()
	{
		$e = new TestEntity;
		$e->___event($e, 'load', $this->r, array('xxx' => 'yyy', 'id' => 1));
		$this->assertSame(array('xxx' => 'yyy', 'id' => 1), $this->readAttribute($e, 'values'));
	}

	public function testBadId()
	{
		$e = new TestEntity;
		$this->setExpectedException('UnexpectedValueException', "Param TestEntity::\$id must be 'id', 'integer' given");
		$e->___event($e, 'load', $this->r, array('id' => 0));
	}

}
