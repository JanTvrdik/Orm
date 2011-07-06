<?php

use Orm\PerformanceHelper;
use Orm\RepositoryContainer;

require_once dirname(__FILE__) . '/../../../boot.php';

/**
 * @covers Orm\PerformanceHelper::__construct
 * @covers Orm\PerformanceHelper::access
 * @covers Orm\PerformanceHelper::get
 */
class PerformanceHelper_Base_Test extends TestCase
{
	private $r;
	private $originCb;
	private $cache;
	private $cb = __CLASS__;

	protected function setUp()
	{
		$m = new RepositoryContainer;
		$this->r = $m->tests;
		$this->wipe();
		$this->originCb = PerformanceHelper::$keyCallback;
		$this->cache = new ArrayObject;
		PerformanceHelper_Base_PerformanceHelper::$cache = $this->cache;
		PerformanceHelper::$keyCallback = array($this , 'cb');
	}

	private function wipe()
	{
		if (PHP_VERSION_ID < 50300)
		{
			throw new PHPUnit_Framework_IncompleteTestError('php 5.2 (setAccessible)');
		}
		$r = new ReflectionProperty('Orm\PerformanceHelper', 'toLoad');
		$r->setAccessible(true);
		$r->setValue(NULL);
		PerformanceHelper::$toSave = NULL;
	}

	protected function tearDown()
	{
		PerformanceHelper::$keyCallback = $this->originCb;
		$this->wipe();
	}

	public function cb()
	{
		return $this->cb;
	}

	public function test()
	{
		$h = new PerformanceHelper_Base_PerformanceHelper($this->r);
		$this->assertAttributeSame('tests', 'repositoryName', $h);
		$this->assertAttributeSame(array(), 'access', $h);
		$this->assertAttributeSame(array('tests' => array()), 'toSave', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertAttributeSame(array(), 'toLoad', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertSame(NULL, $h->get());

		$h->access(1);
		$h->access(2);

		$this->assertAttributeSame(array(1 => 1, 2 => 2), 'access', $h);
		$this->assertAttributeSame(array('tests' => array(1 => 1, 2 => 2)), 'toSave', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertSame(NULL, $h->get());
	}

	public function testCache()
	{
		$this->cache[__CLASS__]['tests'] = array(1 => 1, 2 => 2);
		$this->cache['*']['tests'] = array(3 => 3);
		$h = new PerformanceHelper_Base_PerformanceHelper($this->r);
		$this->assertAttributeSame('tests', 'repositoryName', $h);
		$this->assertAttributeSame(array(), 'access', $h);
		$this->assertAttributeSame(array('tests' => array()), 'toSave', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertAttributeSame(array('tests' => array(1 => 1, 2 => 2)), 'toLoad', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertSame(array(1 => 1, 2 => 2), $h->get());
		$this->assertAttributeSame(array('tests' => NULL), 'toLoad', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertSame(NULL, $h->get());
	}

	public function testCacheStar()
	{
		$this->cb = NULL;
		$this->cache[__CLASS__]['tests'] = array(1 => 1, 2 => 2);
		$this->cache['*']['tests'] = array(3 => 3);
		$h = new PerformanceHelper_Base_PerformanceHelper($this->r);
		$this->assertAttributeSame('tests', 'repositoryName', $h);
		$this->assertAttributeSame(array(3 => 3), 'access', $h);
		$this->assertAttributeSame(array('tests' => array(3 => 3)), 'toSave', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertAttributeSame(array('tests' => array(3 => 3)), 'toLoad', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertSame(array(3 => 3), $h->get());
		$this->assertAttributeSame(array('tests' => NULL), 'toLoad', 'PerformanceHelper_Base_PerformanceHelper');
		$this->assertSame(NULL, $h->get());

		$h->access(2);
		$this->assertAttributeSame(array('tests' => array(3 => 3, 2 => 2)), 'toSave', 'PerformanceHelper_Base_PerformanceHelper');
	}

	/**
	 * @covers Orm\Repository::getById
	 */
	public function testGetById_IdNotPerformedNotExist()
	{
		$this->cache[__CLASS__]['tests'] = array(1 => 1, 2 => 2, 4 => 4, 999 => 999);
		$h = new PerformanceHelper_Base_PerformanceHelper($this->r);

		$r = new ReflectionProperty('Orm\Repository', 'performanceHelper');
		$r->setAccessible(true);
		$r->setValue($this->r, $h);

		$e = $this->r->getById(3);
		$this->assertSame(NULL, $e);

		$entities = $this->readAttribute($this->r, 'entities');
		$this->assertSame(5, count($entities));
		$this->assertSame(false, $entities[999]);
		$this->assertSame(false, $entities[4]);
		$this->assertSame(false, $entities[3]);
		$this->assertSame($this->r->getById(2), $entities[2]);
		$this->assertSame($this->r->getById(1), $entities[1]);
	}

	/**
	 * @covers Orm\Repository::getById
	 */
	public function testGetById_IdNotPerformedExist()
	{
		$this->cache[__CLASS__]['tests'] = array(1 => 1, 4 => 4, 999 => 999);
		$h = new PerformanceHelper_Base_PerformanceHelper($this->r);

		$r = new ReflectionProperty('Orm\Repository', 'performanceHelper');
		$r->setAccessible(true);
		$r->setValue($this->r, $h);

		$e = $this->r->getById(2);
		$this->assertInstanceOf('TestEntity', $e);
		$this->assertSame(2, $e->id);

		$entities = $this->readAttribute($this->r, 'entities');
		$this->assertSame(4, count($entities));
		$this->assertSame(false, $entities[999]);
		$this->assertSame(false, $entities[4]);
		$this->assertSame($this->r->getById(2), $entities[2]);
		$this->assertSame($this->r->getById(1), $entities[1]);
	}

	/**
	 * @covers Orm\Repository::getById
	 */
	public function testGetById_IdIsPerformedNotExist()
	{
		$this->cache[__CLASS__]['tests'] = array(1 => 1, 2 => 2, 4 => 4, 999 => 999);
		$h = new PerformanceHelper_Base_PerformanceHelper($this->r);

		$r = new ReflectionProperty('Orm\Repository', 'performanceHelper');
		$r->setAccessible(true);
		$r->setValue($this->r, $h);

		$e = $this->r->getById(4);
		$this->assertSame(NULL, $e);

		$entities = $this->readAttribute($this->r, 'entities');
		$this->assertSame(4, count($entities));
		$this->assertSame(false, $entities[999]);
		$this->assertSame(false, $entities[4]);
		$this->assertSame($this->r->getById(2), $entities[2]);
		$this->assertSame($this->r->getById(1), $entities[1]);
	}

	/**
	 * @covers Orm\Repository::getById
	 */
	public function testGetById_IdIsPerformedExist()
	{
		$this->cache[__CLASS__]['tests'] = array(1 => 1, 2 => 2, 4 => 4, 999 => 999);
		$h = new PerformanceHelper_Base_PerformanceHelper($this->r);

		$r = new ReflectionProperty('Orm\Repository', 'performanceHelper');
		$r->setAccessible(true);
		$r->setValue($this->r, $h);

		$e = $this->r->getById(1);
		$this->assertInstanceOf('TestEntity', $e);
		$this->assertSame(1, $e->id);

		$entities = $this->readAttribute($this->r, 'entities');
		$this->assertSame(4, count($entities));
		$this->assertSame(false, $entities[999]);
		$this->assertSame(false, $entities[4]);
		$this->assertSame($this->r->getById(2), $entities[2]);
		$this->assertSame($this->r->getById(1), $entities[1]);
		$this->assertSame($e, $entities[1]);
	}

}