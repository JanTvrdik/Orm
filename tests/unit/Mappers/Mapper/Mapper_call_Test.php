<?php

require_once dirname(__FILE__) . '/../../../boot.php';

/**
 * @covers Mapper::__call
 */
class Mapper_call_Test extends TestCase
{
	private $m;
	protected function setUp()
	{
		Mapper_call_Collection::$last = NULL;
		$this->m = new Mapper_call_Mapper(new TestsRepository(new Model));
	}

	public function testFindBy()
	{
		$this->assertSame('xyz', $this->m->findByName('abc'));
		$this->assertSame(array('findByName', array('abc')), Mapper_call_Collection::$last);
	}

	public function testGetBy()
	{
		$this->assertSame('xyz', $this->m->getByName('abc'));
		$this->assertSame(array('getByName', array('abc')), Mapper_call_Collection::$last);
	}

	public function testFindByCaseInsensitive()
	{
		$this->assertSame('xyz', $this->m->fIndbyName('abc'));
		$this->assertSame(array('fIndbyName', array('abc')), Mapper_call_Collection::$last);
	}

	public function testGetByCaseInsensitive()
	{
		$this->assertSame('xyz', $this->m->gEtbyName('abc'));
		$this->assertSame(array('gEtbyName', array('abc')), Mapper_call_Collection::$last);
	}

	public function testUnexists()
	{
		$this->setExpectedException('MemberAccessException', 'Call to undefined method Mapper_call_Mapper::getXyz()');
		$this->m->getXyz('abc');
	}

	public function testHasMethod()
	{
		$this->assertSame('getByXyz', $this->m->getByXyz('abc'));
		$this->assertSame(NULL, Mapper_call_Collection::$last);
	}

	public function testHasProtectedMethod()
	{
		if (PHP_VERSION_ID < 50300)
		{
			$this->markTestIncomplete('php 52: pri protected misto volani __call vyhazuje fatal error');
		}
		$this->setExpectedException('MemberAccessException', 'Call to undefined method Mapper_call_Mapper::getByProtected()');
		$this->m->getByProtected('abc');
	}

}
