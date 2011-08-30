<?php

use Orm\DibiPersistenceHelper;
use Orm\Entity;
use Orm\SqlConventional;
use Orm\RepositoryContainer;

class DibiPersistenceHelper_DibiPersistenceHelper extends DibiPersistenceHelper
{
	public function call($method, array $args = array())
	{
		return call_user_func_array(array($this, $method), $args);
	}
}

/**
 * @property mixed $miXed
 * @property mixed $miXed2
 * @property mixed $miXed3
 */
class DibiPersistenceHelper_Entity extends Entity
{
	public function getMiXed4()
	{
		return 4;
	}
}

abstract class DibiPersistenceHelper_Test extends TestCase
{

	/** @var DibiPersistenceHelper_Entity */
	protected $e;
	protected $r;
	/** @var DibiPersistenceHelper_DibiPersistenceHelper */
	protected $h;
	protected $model;

	/** @var DibiMockExpectedMySqlDriver */
	protected $d;

	protected function setUp()
	{
		$this->model = new RepositoryContainer;
		$this->r = $this->model->DibiMapper_Connected_Dibi;
		$m = $this->r->getMapper();
		$this->d = $m->getConnection()->getDriver();
		$this->h = new DibiPersistenceHelper_DibiPersistenceHelper($m->getConnection(), $m->conventional, 'table');
		$this->e = new DibiPersistenceHelper_Entity;
		$this->e->miXed = 1;
		$this->e->miXed2 = 2;
		$this->e->miXed3 = 3;
	}

	protected function tearDown()
	{
		$this->d->disconnect();
	}

}
