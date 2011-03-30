<?php

require_once dirname(__FILE__) . '/IConventional.php';

class SqlConventional extends Object implements IConventional
{
	private static $staticCache = array();

	private $cache = array();

	public function __construct(IMapper $mapper)
	{
		$this->cache = & self::$staticCache[$mapper->getRepository()->getRepositoryName()];
		$this->loadFk((array) $mapper->getRepository()->getEntityClassName());
	}

	/**
	 * camelCase -> underscore_separated.
	 * @param  string
	 * @return string
	 */
	protected function storageFormat($key)
	{
		$s = preg_replace('#(.)(?=[A-Z])#', '$1_', $key);
		$s = strtolower($s);
		return $s;
	}

	/**
	 * underscore_separated -> camelCase.
	 * @param  string
	 * @return string
	 */
	protected function entityFormat($key)
	{
		$s = strtolower($key);
		$s = preg_replace('#_(?=[a-z])#', ' ', $s);
		$s = substr(ucwords('x' . $s), 1);
		$s = str_replace(' ', '', $s);
		return $s;
	}

	/**
	 * fk
	 * @param  string
	 * @return string
	 */
	public function foreignKeyFormat($s)
	{
		return $s . '_id';
	}

	final public function formatEntityToStorage($data)
	{
		$result = array();
		foreach ($data as $key => $value)
		{
			if (isset($this->cache['fk']['entity'][$key]))
			{
				$k = $this->cache['fk']['entity'][$key];
			}
			else if (isset($this->cache['entity'][$key]))
			{
				$k = $this->cache['entity'][$key];
			}
			else
			{
				$k = $this->cache['entity'][$key] = $this->storageFormat($key);
			}
			$result[$k] = $value;
		}
		return $result;
	}

	final public function formatStorageToEntity($data)
	{
		$result = array();
		foreach ($data as $key => $value)
		{
			if (isset($this->cache['fk']['storage'][$key]))
			{
				$k = $this->cache['fk']['storage'][$key];
			}
			else if (isset($this->cache['storage'][$key]))
			{
				$k = $this->cache['storage'][$key];
			}
			else
			{
				$k = $this->cache['storage'][$key] = $this->entityFormat($key);
			}
			$result[$k] = $value;
		}
		return $result;
	}

	final private function loadFk(array $entityNames)
	{
		if (!isset($this->cache['fk']))
		{
			$result = array();
			if ($this->foreignKeyFormat('test') !== 'test') // pokracovat jen kdyz se fk format lisi
			{
				foreach ($entityNames as $entityName)
				{
					foreach (MetaData::getEntityRules($entityName) as $name => $rule)
					{
						if ($rule['relationship'] !== MetaData::ManyToOne AND $rule['relationship'] !== MetaData::OneToOne) continue;
						$fk = $this->foreignKeyFormat($this->storageFormat($name));
						$result['storage'][$fk] = $name;
						$result['entity'][$name] = $fk;
					}
				}
			}
			$this->cache['fk'] = $result;
		}
	}

	public function getManyToManyTableName(IRepository $first, IRepository $second)
	{
		return $first->getRepositoryName() . '_x_' . $second->getRepositoryName();
	}





	/** @ignore @deprecated */
	final public function format($data)
	{
		throw new DeprecatedException();
		return $this->formatEntityToStorage($data);
	}
	/** @ignore @deprecated */
	final public function unformat($data)
	{
		throw new DeprecatedException();
		return $this->formatStorageToEntity($data);
	}
	/** @ignore @deprecated */
	final protected function formatKey($key)
	{
		throw new DeprecatedException();
		return $this->storageFormat($key);
	}
	/** @ignore @deprecated */
	final protected function unformatKey($key)
	{
		throw new DeprecatedException();
		return $this->entityFormat($key);
	}

}
