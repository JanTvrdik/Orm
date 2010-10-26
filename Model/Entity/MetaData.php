<?php

class MetaData extends Object
{
	const READ = 1;
	const WRITE = 2;
	const READWRITE = 3;

	const ManyToMany ='m:m';
	const OneToMany ='1:m';

	const ManyToOne ='m:1';
	const OneToOne ='1:1';

	private $entityClass;

	private $properties = array();

	public function __construct($entityClass)
	{
		if ($entityClass instanceof IEntity)
		{
			$entityClass = get_class($entityClass);
		}
		else
		{
			if (!class_exists($entityClass)) throw new InvalidStateException();
			$r = new ClassReflection($entityClass);
			$entityClass = $r->getName();
			if (!$r->implementsInterface('IEntity')) throw new InvalidStateException();
		}
		$this->entityClass = $entityClass;
	}

	public function addProperty($name, $types, $access, $since = NULL)
	{
		if (isset($this->properties[$name]))
		{
			if ($since === NULL OR $this->properties[$name]->getSince() === $since)
			{
				throw new Exception(); // todo
			}
			$this->properties[$name] = new MetaDataProperty($this, $name, $types, $access, $since);
		}
		else
		{
			$this->properties[$name] = new MetaDataProperty($this, $name, $types, $access, $since);
		}

		return $this->properties[$name];
	}

	public function getEntityClass()
	{
		return $this->entityClass;
	}

	public function toArray()
	{
		$properties = array();
		foreach ($this->properties as $name => $property)
		{
			$properties[$name] = $property->toArray();
		}

		$methods = array_diff(get_class_methods($this->entityClass), get_class_methods('Entity'));
		$methods[] = 'getId';
		// TODO neumoznuje pouzit vlastni IEntity
		foreach ($methods as $method)
		{
			$m = substr($method, 0, 3);
			if ($m === 'get' OR $m === 'set')
			{
				$var = substr($method, 3);
				if ($var{0} != '_') $var{0} = $var{0} | "\x20"; // lcfirst
			}
			else if (substr($method, 0, 2) === 'is')
			{
				$m = 'get';
				$var = substr($method, 2);
				if ($var{0} != '_') $var{0} = $var{0} | "\x20"; // lcfirst
				if (!isset($properties[$var]) OR $properties[$var]['types'] !== array('bool'))
				{
					continue;
				}
			}
			else
			{
				continue;
			}

			if (isset($properties[$var][$m]))
			{
				$properties[$var][$m]['method'] = $method;
			}
		}

		return $properties;
	}
}

class MetaDataProperty extends Object
{
	private $class, $name;

	private $data = array(
		'types' => array(),
		'get' => NULL,
		'set' => NULL,
		'since' => NULL,
		'relationship' => NULL,
		'relationshipParam' => NULL,
		'default' => NULL,
		'enum' => NULL,
	);

	public function toArray()
	{
		return $this->data;
	}

	public function __construct(MetaData $meta, $name, $types, $access, $since = NULL)
	{
		$this->class = $meta->getEntityClass();
		$this->name = $name;
		$this->data['since'] = $since;
		$this->setTypes($types);
		$this->setAccess($access);
	}

	public function getSince()
	{
		return $this->data['since'];
	}

	/**
	 * @param array|string
	 */
	protected function setTypes($types)
	{
		if (is_array($types))
		{
			$types = array_map('strtolower', $types);
		}
		else if(is_scalar($types))
		{
			$types = explode('|',strtolower($types));
		}

		if (in_array('mixed', $types))
		{
			$types = array();
		}

		$this->data['types'] = $types;
	}

	/**
	 * @param MetaData::READ|MetaData::READWRITE
	 */
	protected function setAccess($access)
	{
		if ($access === NULL) $access = MetaData::READWRITE;
		if ($access === MetaData::WRITE) throw new InvalidStateException("Neni mozne vytvaret write-only polozky: {$this->class}::\${$this->name}");
		if (!in_array($access, array(MetaData::READ, MetaData::READWRITE), true)) throw new Exception();
		$this->data['get'] = $access & MetaData::READ ? array('method' => NULL) : NULL;
		$this->data['set'] = $access & MetaData::WRITE ? array('method' => NULL) : NULL;
	}

	public function setOneToOne($repositoryName)
	{
		if (isset($this->data['relationship'])) throw new InvalidStateException("Already has relationship in {$this->class}::\${$this->name}");
		if (!$repositoryName)
		{
			throw new InvalidStateException("You must specify foreign repository in {$this->class}::\${$this->name}");
		}
		else if (!Model::get()->isRepository($repositoryName))
		{
			throw new InvalidStateException("$repositoryName isn't repository in {$this->class}::\${$this->name}");
		}

		$this->data['relationship'] = MetaData::OneToOne;
		$this->data['relationshipParam'] = $repositoryName;
	}

	public function setManyToOne($repositoryName)
	{
		$this->setOnetoOne($repositoryName);
		$this->data['relationship'] = MetaData::ManyToOne;
	}

	private function setToMany($relationship)
	{
		if (isset($this->data['relationship'])) throw new InvalidStateException("Already has relationship in {$this->class}::\${$this->name}");
		if (count($this->data['types']) != 1) throw new InvalidStateException();
		$relationshipClassName = current($this->data['types']);
		if (!class_exists($relationshipClassName)) throw new InvalidStateException();
		$parents = class_parents($relationshipClassName);
		if (!isset($parents[$relationship === MetaData::ManyToMany ? 'ManyToMany' : 'OneToMany'])) throw new InvalidStateException();

		$this->data['relationship'] = $relationship;
		$this->data['relationshipParam'] = $relationshipClassName;
	}

	public function setOneToMany()
	{
		$this->setToMany(MetaData::OneToMany);
	}

	public function setManyToMany()
	{
		$this->setToMany(MetaData::ManyToMany);
	}

	private function builtSelf($string)
	{
		if (substr($string, 0, 6) === 'self::')
		{
			$string = str_replace('self::', "{$this->class}::", $string);
		}
		return $string;
	}

	public function builtParamsEnum($string)
	{
		if (preg_match('#^([a-z0-9_-]+::[a-z0-9_-]+)\(\)$#si', trim($string), $tmp))
		{
			$original = $enum = array_keys(callback($this->builtSelf($tmp[1]))->invoke());
		}
		else
		{
			$original = $enum = array();
			foreach (array_map('trim', explode(',', $string)) as $d)
			{
				$d = $this->builtSelf($d);

				if (is_numeric($d))
				{
					$value = (float) $d;
				}
				else if (defined($d))
				{
					$value = constant($d);
				}
				else if (strpos($d, '::') !== false)
				{
					throw new Exception();
				}
				$enum[] = $value;
				$original[] = $d;
			}
		}
		return array($enum, implode(', ', $original));
	}

	public function builtParamsDefault($string)
	{
		$string = $this->builtSelf(trim($string));
		if (is_numeric($string))
		{
			$string = (float) $string;
		}
		else if (defined($string))
		{
			$string = constant($string);
		}
		else if (strpos($string, '::') !== false)
		{
			throw new Exception();
		}
		return array($string);
	}

	public function setEnum(array $values, $original = NULL)
	{
		$this->data['enum'] = array('constants' => array_unique($values), 'original' => $original ? $original : implode(', ', $values));
		// todo original zrusit
	}

	public function setDefault($value)
	{
		$this->data['default'] = $value;
	}

}