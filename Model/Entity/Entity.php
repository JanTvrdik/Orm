<?php

require_once dirname(__FILE__) . '/IEntity.php';

require_once dirname(__FILE__) . '/AnnotationMetaData.php';

require_once dirname(__FILE__) . '/ValidationHelper.php';

require_once dirname(__FILE__) . '/EntityToArray.php';

/**
 * @property-read int $id
 */
abstract class Entity extends Object implements IEntity
{
	const ENTITY_TO_ID = EntityToArray::AS_ID; // deprecated
	const ENTITY_TO_ARRAY = EntityToArray::AS_ARRAY; // deprecated

	const DEFAULT_VALUE = "\0";

	private $values = array();

	private $valid = array();

	private $rules;

	private $repository;

	private $changed = false;

	public function __construct()
	{
		$this->changed = true;
		$this->___event($this, 'create');
	}

	public function __toString()
	{
		try {
			// todo mozna zrusit
			return isset($this->id) ? (string) $this->id : '';
		} catch (Exception $e) {
			Debug::toStringException($e);
		}
	}

	/** @var int */
	final public function getId()
	{
		$id = $this->getValue('id');
		if (!$id) throw new InvalidStateException('You must persist entity first');
		return $id;
	}

	public function __clone()
	{
		$this->valid['id'] = false;
		$this->values['id'] = NULL;
	}

	protected static function createEntityRules($entityClass)
	{
		return AnnotationMetaData::getEntityParams($entityClass);
	}

	final protected static function getEntityRules($entityClass)
	{
		static $cache = array();
		if (!isset($cache[$entityClass]))
		{
			if (!class_exists($entityClass)) throw new InvalidStateException("Class '$entityClass' doesn`t exists");
			$implements = class_implements($entityClass);
			if (!isset($implements['IEntity'])) throw new InvalidStateException("'$entityClass' isn`t instance of IEntity");
			$meta = call_user_func(array($entityClass, 'createEntityRules'), $entityClass);
			if (!($meta instanceof MetaData)) throw new InvalidStateException("It`s expected that 'IEntity::createEntityRules' will return 'MetaData'.");
			$cache[$entityClass] = $meta->toArray();
		}
		return $cache[$entityClass];
	}

	final public function setValues($values)
	{
		foreach ($values as $name => $value)
		{
			if ($this->hasParam($name, self::WRITE))
			{
				$this->__set($name, $value);
			}
			else if (method_exists($this, "set$name"))
			{
				$r = $this->getReflection()->getMethod("set$name");
				if ($r->isPublic())
				{
					$r->invoke($this, $value);
				}
			}
			else if (property_exists($this, $name))
			{
				$r = $this->getReflection()->getProperty($name);
				if ($r->isPublic())
				{
					$r->setValue($this, $value);
				}
			}
		}
	}

	final public function toArray($mode = EntityToArray::AS_IS)
	{
		return EntityToArray::toArray($this, $this->rules, $mode);
	}

	const EXISTS = NULL;
	const READ = 'r';
	const WRITE = 'w';
	const READWRITE = 'rw';
	final public function hasParam($name, $mode = self::EXISTS)
	{
		if ($mode === self::EXISTS) return isset($this->rules[$name]);
		if (!isset($this->rules[$name])) return false;

		$rule = $this->rules[$name];
		if ($mode === self::READWRITE) return isset($rule['get']) AND isset($rule['set']);
		else if ($mode === self::READ) return isset($rule['get']);
		else if ($mode === self::WRITE) return isset($rule['set']);
		return false;
	}

	final protected function getValue($name, $need = true)
	{
		if (!isset($this->rules[$name]))
		{
			throw new MemberAccessException("Cannot read an undeclared property ".get_class($this)."::\$$name.");
		}

		$rule = $this->rules[$name];

		if (!isset($rule['get']))
		{
			throw new MemberAccessException("Cannot read to a write-only property ".get_class($this)."::\$$name.");
		}

		$value = self::DEFAULT_VALUE;
		$valid = false;
		if (isset($this->values[$name]) OR array_key_exists($name, $this->values))
		{
			$valid = isset($this->valid[$name]) ? $this->valid[$name] : false;
			$value = $this->values[$name];
		}
		else if ($this->getGeneratingRepository(false)) // lazy load
		{
			if ($lazyLoadParams = $this->getGeneratingRepository()->lazyLoad($this, $name))
			{
				foreach ($lazyLoadParams as $n => $v)
				{
					$this->values[$n] = $v;
					$this->valid[$n] = false;
				}
				if (array_key_exists($name, $this->values))
				{
					$value = $this->values[$name];
				}
			}
		}

		if (!$valid)
		{
			$tmpChanged = $this->changed;
			try {
				if (isset($rule['set']))
				{
					$this->__set($name, $value);
				}
				else
				{
					$this->setValueHelper($name, $value);
				}
			} catch (UnexpectedValueException $e) {
				$this->changed = $tmpChanged;
				if ($need) throw $e;
				return NULL;
			}
			$this->changed = $tmpChanged;
			$value = isset($this->values[$name]) ? $this->values[$name] : NULL; // todo kdyz neni nastaveno muze to znamenat neco spatne, vyhodit chybu?
		}

		return $value;
	}

	final protected function setValue($name, $value)
	{
		if (!isset($this->rules[$name]))
		{
			throw new MemberAccessException("Cannot write to an undeclared property ".get_class($this)."::\$$name.");
		}

		$rule = $this->rules[$name];

		if (!isset($rule['set']))
		{
			throw new MemberAccessException("Cannot write to a read-only property ".get_class($this)."::\$$name.");
		}

		$this->setValueHelper($name, $value);

		return $this;
	}

	// todo zvazit
	final public function getGeneratingRepository($need = true) // todo generating je blbost, lepsi nazev by bylo neco jako getOwningReppository nebo jen getRepository
	{
		if ($this->repository) return $this->repository;
		else if (!$need) return NULL;
		else throw new InvalidStateException();
	}

	final public function getModel($need = true)
	{
		$need = false; // todo
		if ($this->getGeneratingRepository($need))
		{
			return $this->getGeneratingRepository()->getModel();
		}
		return Model::get(); // todo di
		return NULL;
	}

	final public function isChanged()
	{
		return $this->__isset('id') ? $this->changed : true;
	}

	final public function getIterator()
	{
		return new ArrayIterator($this->toArray());
	}


	final public function offsetExists($name)
	{
		return $this->__isset($name);
	}
	final public function offsetGet($name)
	{
		return $this->__get($name);
	}
	final public function offsetSet($name, $value)
	{
		return $this->__set($name, $value);
	}
	final public function offsetUnset($name)
	{
		throw new NotSupportedException();
	}




	final public function & __get($name)
	{
		if (!isset($this->rules[$name]))
		{
			$tmp = parent::__get($name);
			return $tmp;
		}

		$rule = $this->rules[$name];

		if (!isset($rule['get']))
		{
			throw new MemberAccessException("Cannot read to a write-only property ".get_class($this)."::\$$name.");
		}

		$value = NULL;
		if ($rule['get']['method'])
		{
			$value = $this->{$rule['get']['method']}(); // todo mohlo by zavolat private metodu, je potreba aby vse bylo final
		}
		else
		{
			$value = $this->getValue($name);
		}

		return $value;
	}

	final public function __set($name, $value)
	{
		if (!isset($this->rules[$name]))
		{
			return parent::__set($name, $value);
		}

		$rule = $this->rules[$name];

		if (!isset($rule['set']))
		{
			throw new MemberAccessException("Cannot write to a read-only property ".get_class($this)."::\$$name.");
		}
		if ($rule['set']['method'])
		{
			if ($value === self::DEFAULT_VALUE)
			{
				$value = $this->getDefaultValueHelper($name, $rule);
			}
			$this->{$rule['set']['method']}($value); // todo mohlo by zavolat private metodu, je potreba aby vse bylo final
		}
		else
		{
			$this->setValue($name, $value);
		}

		return $this;
	}

	final public function __call($name, $args)
	{
		$m = substr($name, 0, 3);
		if ($m === 'get' OR ($m === 'set' AND array_key_exists(0, $args)))
		{
			$var = substr($name, 3);
			if ($var{0} != '_') $var{0} = $var{0} | "\x20"; // lcfirst

			if (isset($this->rules[$var]))
			{
				return $this->{'__' . $m}($var, $m === 'set' ? $args[0] : NULL);
			}
		}
		else if (substr($name, 0, 2) === 'is')
		{
			$var = substr($name, 2);
			if ($var{0} != '_') $var{0} = $var{0} | "\x20"; // lcfirst
			if (isset($this->rules[$var]) AND $this->rules[$var]['types'] === array('bool'))
			{
				return $this->__get($var);
			}
		}

		return parent::__call($name, $args);
	}

	final public function __isset($name)
	{
		if (!isset($this->rules[$name]))
		{
			return parent::__isset($name);
		}
		else if (isset($this->rules[$name]['get']))
		{
			try {
				return $this->__get($name) !== NULL;
			} catch (Exception $e) {
				return false;
			}
		}

		return false;
	}





	final private function getDefaultValueHelper($name, $rule)
	{
		$default = NULL;
		if (isset($rule['default']))
		{
			$default = $rule['default'];
		}
		else
		{
			$defaultMethod = "getDefault$name";
			$defaultMethod{10} = $defaultMethod{10} & "\xDF"; // ucfirst
			if (method_exists($this, $defaultMethod))
			{
				$default = $this->{$defaultMethod}();
			}
		}
		return $default;
	}


	final private function setValueHelper($name, $value)
	{
		$rule = $this->rules[$name];

		if ($value === self::DEFAULT_VALUE)
		{
			$value = $this->getDefaultValueHelper($name, $rule);
		}

		if (($rule['relationship'] === MetaData::ManyToOne OR $rule['relationship'] === MetaData::OneToOne) AND !($value instanceof IEntity))
		{
			$id = (string) $value;
			if ($id)
			{
				$value = Model::get()->getRepository($rule['relationshipParam'])->getById($id);
			}
		}
		else if ($rule['relationship'] === MetaData::OneToMany OR $rule['relationship'] === MetaData::ManyToMany)
		{

			if (!isset($this->values[$name]) OR !($this->values[$name] instanceof IRelationship))
			{
				$tmp = new $rule['relationshipParam']($this);
			}
			else
			{
				$tmp = $this->values[$name];
			}
			if ($value !== NULL) $tmp->set($value);
			$value = $tmp;
		}

		if (isset($rule['enum']))
		{
			if (in_array($value, $rule['enum']['constants'], true)) {}
			else if (($tmp = array_search($value, $rule['enum']['constants'])) !== false)
			{
				$value = $rule['enum']['constants'][$tmp];
			}
			else if (in_array('null', $rule['types']) AND $value === NULL)
			{
				$value = NULL;
			}
			else
			{
				throw new UnexpectedValueException("Param ".get_class($this)."::\$$name must be '{$rule['enum']['original']}', '" . (is_object($value) ? 'object ' . get_class($value) : (is_scalar($value) ? $value : gettype($value))) . "' given");
			}
		}
		if (!ValidationHelper::isValid($rule['types'], $value))
		{
			$type = implode('|',$rule['types']);
			throw new UnexpectedValueException("Param ".get_class($this)."::\$$name must be '$type', '" . (is_object($value) ? get_class($value) : gettype($value)) . "' given");
		}

		$this->values[$name] = $value;
		$this->valid[$name] = true;
		$this->changed = true;
	}


	/** Vytvorena nova entita */
	protected function onCreate()
	{
		$this->rules = self::getEntityRules(get_class($this));
		$this->checkEvent = true;
	}

	/** Vytazena z mapperu */
	protected function onLoad(IRepository $repository)
	{
		$this->rules = self::getEntityRules(get_class($this));
		$this->checkEvent = true;
	}

	/** Pred persistovanim (insert nebo update) */
	protected function onBeforePersist(IRepository $repository)
	{
		$this->checkEvent = true;
	}
	/** Po persistovani (insert nebo update) */
	protected function onAfterPersist(IRepository $repository)
	{
		$this->checkEvent = true;
	}
	/** Behem persistovani, vsechny subentity nemusi byt jeste persistovany */
	final protected function onPersist(IRepository $repository, $id)
	{
		if (!$id) throw new UnexpectedValueException();
		$this->values['id'] = $id;
		$this->valid['id'] = false;
		$this->changed = false;
		$this->repository = $repository;
		$this->checkEvent = true;
	}

	/** Pred vymazanim */
	protected function onBeforeDelete(IRepository $repository)
	{
		$this->checkEvent = true;
	}
	/** Po vymazani */
	protected function onAfterDelete(IRepository $repository)
	{
		$this->values['id'] = NULL;
		$this->valid['id'] = false;
		$this->changed = true;
		$this->repository = NULL;
		$this->checkEvent = true;
	}

	/** Persistovane zmeny (update) */
	protected function onBeforeUpdate(IRepository $repository)
	{
		$this->checkEvent = true;
	}
	/** Persistovane zmeny (update) */
	protected function onAfterUpdate(IRepository $repository)
	{
		$this->checkEvent = true;
	}

	/** Persistovane zmeny (insert) */
	protected function onBeforeInsert(IRepository $repository)
	{
		$this->checkEvent = true;
	}
	/** Persistovane zmeny (insert) */
	protected function onAfterInsert(IRepository $repository)
	{
		$this->checkEvent = true;
	}








	/**
	 * @internal
	 */
	final public static function ___create($entityName, array $data, IRepository $repository)
	{
		$entity = unserialize("O:".strlen($entityName).":\"$entityName\":0:{}");
		if (!($entity instanceof IEntity)) throw new InvalidStateException();
		// TODO kdyz je instanceof self tak pouzivat private pristup, jinak vymyslet neco jineho

		$entity->repository = $repository;
		$entity->values = $data;
		$entity->valid = array();
		return $entity;
	}







	private $checkEvent;
	/**
	 * @internal
	 */
	final public static function ___event(IEntity $entity, $event, IRepository $repository = NULL, $id = NULL)
	{
		$method = 'on' . ucfirst($event);
		$entity->checkEvent = NULL;
		if ($id === NULL)
		{
			$entity->{$method}($repository);
		}
		else
		{
			$entity->{$method}($repository, $id);
		}

		if ($entity->checkEvent !== true)
		{
			$class = get_class($entity);
			throw new InvalidStateException("Method $class::$method() or its descendant doesn't call parent::$method().");
		}
	}

	/**
	 * @internal
	 */
	final public static function ___getFk($entityName)
	{
		$result = array();
		foreach (Entity::getEntityRules($entityName) as $name => $rule)
		{
			if ($rule['relationship'] !== MetaData::ManyToOne AND $rule['relationship'] !== MetaData::OneToOne) continue;
			$result[$name] = $rule['relationshipParam'];
		}
		return $result;
	}



	/** @deprecated */
	final protected function check(){}
	/** @deprecated */
	final public function toPlainArray()
	{
		return $this->toArray(self::ENTITY_TO_ID);
	}
}
