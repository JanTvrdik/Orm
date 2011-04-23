<?php

class Repository_remove_Entity extends Entity
{

}

class Repository_remove_Repository extends Repository
{
	protected $entityClassName = 'TestEntity';
}

class Repository_remove_Mapper extends TestsMapper
{
	public $count = 0;

	public $returnNull = false;

	public function remove(IEntity $entity)
	{
		$this->count++;
		if ($this->returnNull) return NULL;
		return parent::remove($entity);
	}
}
