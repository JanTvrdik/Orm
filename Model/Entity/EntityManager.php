<?php

class EntityManager extends Object // rename AnotationMetaDataZiskavac
{
	public static function getEntityParams($class)
	{
		if (!class_exists($class)) throw new InvalidStateException();
		else if (!is_subclass_of($class, 'Entity')) throw new InvalidStateException();

		$metaData = new MetaData($class);
		$params = array();
		$classes = array();
		$_class = $class;
		while (class_exists($_class))
		{
			$classes[] = $_class;
			if ($_class === 'Entity') break;
			$_class = get_parent_class($_class);
		}

		foreach (array_reverse($classes) as $_class)
		{
			$annotations = AnnotationsParser::getAll(new ClassReflection($_class));

			if (isset($annotations['property']))
			{
				foreach ($annotations['property'] as $string)
				{
					if (preg_match('#^(-read|-write)?\s?([a-z0-9_\|]+)\s+\$([a-z0-9_]+)($|\s(.*)$)#si', $string, $match))
					{
						$property = $match[3];
						$type = $match[2];
						$mode = $match[1];
						$string = $match[4];
					}
					else if (preg_match('#^(-read|-write)?\s?\$([a-z0-9_]+)\s+([a-z0-9_\|]+)($|\s(.*)$)#si', $string, $match))
					{
						$property = $match[2];
						$type = $match[3];
						$mode = $match[1];
						$string = $match[4];
					}
					else if (preg_match('#^(-read|-write)?\s?\$([a-z0-9_]+)($|\s(.*)$)#si', $string, $match))
					{
						$property = $match[2];
						$type = 'mixed';
						$mode = $match[1];
						$string = $match[3];
					}
					else
					{
						throw new InvalidStateException($string);
						//continue;
					}

					if (isset($params[$property]['since']) AND $params[$property]['since'] !== $_class)
					{
						unset($params[$property]);
					}

					$type = explode('|',strtolower($type));
					if (in_array('mixed', $type))
					{
						$type = array();
					}

					if (isset($params[$property]['types']) AND isset($params[$property]['types']) AND $params[$property]['types'] !== $type)
					{
						throw new InvalidStateException('Getter and setter types must be same.');
					}

					if (preg_match('#\{\s*(ManyToOne|OneToOne|m\:1|1\:1)\s+([^\s]*)\s*\}#si', $string, $match))
					{
						$annotations['foreignKey'][] = "$$property " . $match[2];
					}

					$params[$property]['types'] = $type;

					if (!$mode OR $mode === '-read')
					{
						$params[$property]['get'] = true;
						$params[$property]['since'] = $_class;
					}
					if (!$mode OR $mode === '-write')
					{
						$params[$property]['set'] = true;
						$params[$property]['since'] = $_class;
					}

				}
			}

			if (isset($annotations['fk']))
			{
				if (isset($annotations['foreignKey']))
				{
					$annotations['foreignKey'] = array_merge($annotations['foreignKey'], $annotations['fk']);
				}
				else
				{
					$annotations['foreignKey'] = $annotations['fk'];
				}
			}
			if (isset($annotations['foreignKey']))
			{
				foreach ($annotations['foreignKey'] as $fk)
				{
					if (preg_match('#\s?\$([a-z0-9_]+)\s([a-z0-9_]+)$#si', $fk, $match))
					{
						$property = $match[1];
						$repository = $match[2];
						if (isset($params[$property]))
						{
							if (!isset($params[$property]['fk']))
							{
								if (Model::isRepository($repository))
								{
									$params[$property]['fk'] = $repository;
								}
								else throw new InvalidStateException("$repository isn't repository in $property");
							}
							else throw new InvalidStateException("Already has fk in $property");
						}
						else throw new InvalidStateException("$property not exists");
					}
					else throw new InvalidStateException("Bad fk format in $property.");
				}
			}

			/*if (isset($annotations['method']))
			{
				foreach ($annotations['method'] as $method)
				{

				}
			}*/
		}

		foreach ($params as $property => $param)
		{
			$metaData->add(
				$property,
				$param['types'],
				isset($param['get'], $param['set']) ? MetaData::READWRITE :
					(isset($param['get']) ? MetaData::READ : MetaData::WRITE)
				,
				isset($param['fk']) ? $param['fk'] : NULL,
				$param['since']
			);
		}

		return $metaData->toArray();
	}

}


class MetaData extends Object
{
	const READ = 1;
	const WRITE = 2;
	const READWRITE = 3;

	private $entityClass;
	private $data = array();

	public function __construct($entityClass)
	{
		if ($entityClass instanceof Entity)
		{
			$entityClass = get_class($entityClass);
		}
		else
		{
			if (!class_exists($entityClass)) throw new InvalidStateException();
			else if (!is_subclass_of($entityClass, 'Entity')) throw new InvalidStateException();
		}
		$this->entityClass = $entityClass;
	}

	public function add($name, $types = array(), $access = NULL, $fk = NULL, $since = NULL)
	{
		if (isset($this->data[$name])) throw new Exception($name);

		if (!is_array($types))
		{
			$types = explode('|',strtolower($types));
			if (in_array('mixed', $types))
			{
				$types = array();
			}
		}
		if ($access === NULL) $access = self::READWRITE;


		$this->data[$name] = array(
			'types' => $types,
			'get' => ($access === self::READ OR $access === self::READWRITE) ? array('method' => NULL) : NULL,
			'set' => ($access === self::WRITE OR $access === self::READWRITE) ? array('method' => NULL) : NULL,
			'fk' => $fk,
			'since' => $since,
		);
	}

	public function toArray()
	{
		$params = $this->data;

		$methods = array_diff(get_class_methods($this->entityClass), get_class_methods('Entity'));
		foreach ($methods as $method)
		{
			$m = substr($method, 0, 3);
			if ($m === 'get' OR $m === 'set')
			{
				$var = substr($method, 3);
				if ($var{0} != '_') $var{0} = $var{0} | "\x20"; // lcfirst

				if (isset($params[$var][$m]))
				{
					$params[$var][$m]['method'] = $method;
				}
			}
		}

		return $params;
	}

}
