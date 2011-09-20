<?php

/**
 * @covers Orm\DibiCollection::fetch
 */
class DibiCollection_fetch_Test extends DibiCollection_BaseConnected_Test
{

	public function testOk()
	{
		$this->e(1, false);
		$e = $this->c->fetch();
		$this->assertInstanceOf('TestEntity', $e);
		$this->assertSame(1, $e->id);
		$this->assertSame('boo', $e->string);
	}

	public function testNoRow()
	{
		$this->e(0);
		$e = $this->c->fetch();
		$this->assertSame(NULL, $e);
	}

	public function testFirst()
	{
		$this->e(1, false);
		$e1 = $this->c->fetch();

		$this->d->addExpected('seek', true, 0);
		$this->d->addExpected('fetch', array('id' => 1, 'string' => 'boo'), true);
		$e2 = $this->c->fetch();

		$this->d->addExpected('seek', true, 0);
		$this->d->addExpected('fetch', array('id' => 1, 'string' => 'boo'), true);
		$e3 = $this->c->fetch();

		$this->assertInstanceOf('TestEntity', $e1);
		$this->assertSame(1, $e1->id);
		$this->assertSame('boo', $e1->string);
		$this->assertSame($e1, $e2);
		$this->assertSame($e2, $e3);
	}

}
