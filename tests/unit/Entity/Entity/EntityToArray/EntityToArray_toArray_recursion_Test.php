<?php

require_once dirname(__FILE__) . '/../../../../boot.php';

/**
 * @covers EntityToArray::toArray
 */
class EntityToArray_toArray_recursion_Test extends TestCase
{
	private $om;
	private $mm;
	protected function setUp()
	{
		$m = new Model;
		$this->om = $m->EntityToArray_toArray_recursiton_1m_;
		$this->mm = $m->EntityToArray_toArray_recursiton_mm_;
	}

	public function testOneToMany()
	{
		$this->assertSame(3, EntityToArray::$maxDeep);
		$e = $this->om->getById(1);
		$e->b->add($e);

		$accepted = array(
			'id' => 1,
			'a' => NULL,
			'b' => NULL,
			'string' => 'string',
		);
		$y = $x = $accepted;
		$x['a'] = $y;
		$x['b'] = array($y);
		$accepted['a'] = $x;
		$accepted['b'] = array($x);
		$x = $accepted;
		$accepted['a']['a'] = $x;
		$accepted['a']['b'] = array($x);
		$accepted['b'][0]['a'] = $x;
		$accepted['b'][0]['b'] = array($x);

		$arr = $e->toArray(EntityToArray::AS_ARRAY);

		$this->assertSame($accepted, $arr);
	}

	public function testManyToMany()
	{
		$this->assertSame(3, EntityToArray::$maxDeep);
		$e = $this->mm->getById(1);
		$e->r->add($e);

		$accepted = array(
			'id' => 1,
			'r' => NULL,
			'string' => 'string',
		);
		$x = $accepted;
		$x['r'] = array($x);
		$accepted['r'] = array($x);
		$accepted['r'][0]['r'] = array($accepted);

		$arr = $e->toArray(EntityToArray::AS_ARRAY);

		$this->assertSame($accepted, $arr);
	}

}