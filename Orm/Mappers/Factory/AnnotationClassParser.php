<?php
/**
 * Orm
 * @author Petr Procházka (petr@petrp.cz)
 * @license "New" BSD License
 */

namespace Orm;

use Nette\Object;
use Nette\Reflection\AnnotationsParser;
use ReflectionClass;
use stdClass;

require_once __DIR__ . '/AnnotationClassParserException.php';
require_once __DIR__ . '/AnnotationClassParserNoClassFoundException.php';
require_once __DIR__ . '/AnnotationClassParserMorePossibleClassesException.php';

class AnnotationClassParser extends Object
{

	/** @var array of name => stdClass */
	private $registered = array();

	/**
	 * <pre>
	 * 	$p->register('mapper', 'Orm\IRepository', function ($repositoryClass) {
	 * 		return $repositoryClass . 'Mapper';
	 * 	});
	 * </pre>
	 *
	 * @param string
	 * @param string interface name
	 * @param Callback|Closure|NULL
	 * @return AnnotationClassParser
	 * @throws AnnotationClassParserException
	 */
	public function register($annotation, $interface, $defaultClassFallback = NULL)
	{
		if (isset($this->registered[$annotation]))
		{
			throw new AnnotationClassParserException("Parser '$annotation' is already registered");
		}
		if (!interface_exists($interface))
		{
			throw new AnnotationClassParserException("'$interface' is not valid interface");
		}
		if ($defaultClassFallback !== NULL AND !is_callable($defaultClassFallback)) // todo php52 nema __invoke
		{
			$tmp = is_string($defaultClassFallback) ? $defaultClassFallback : (is_object($defaultClassFallback) ? get_class($defaultClassFallback) : gettype($defaultClassFallback));
			throw new AnnotationClassParserException("'$tmp' is not valid callback");
		}
		$tmp = (object) array(
			'annotation' => $annotation,
			'interface' => $interface,
			'defaultClassFallback' => $defaultClassFallback,
			'cache' => array(),
		);
		$this->registered[$annotation] = $tmp;
		return $this;
	}

	/**
	 * @param string
	 * @param Object
	 * @return string class name
	 * @throws AnnotationClassParserException
	 * @throws AnnotationClassParserNoClassFoundException
	 * @throws AnnotationClassParserMorePossibleClassesException
	 */
	public function get($annotation, $object)
	{
		if (!isset($this->registered[$annotation]))
		{
			throw new AnnotationClassParserException("parser '$annotation' is not registered");
		}
		if (!is_object($object))
		{
			$tmp = gettype($object);
			throw new AnnotationClassParserException("expected object, $tmp given");
		}
		$r = $this->registered[$annotation];
		if (!($object instanceof $r->interface))
		{
			$tmp = get_class($object);
			throw new AnnotationClassParserException("'$tmp' is not instance of {$r->interface}");
		}

		$class = get_class($object);

		if (!isset($r->cache[$class]))
		{
			$result = $this->getByReflection(
				$r,
				new ReflectionClass($class),
				$this->defaultClassFallback($r, $class)
			);
			if (!$result)
			{
				throw new AnnotationClassParserNoClassFoundException("$class::@$annotation no class found");
			}
			$r->cache[$class] = $result;
		}
		return $r->cache[$class];
	}

	/**
	 * @param ReflectionClass
	 * @return array of annotation => array
	 * @see Nette\Reflection\AnnotationsParser
	 */
	protected function getAnnotations(ReflectionClass $reflection)
	{
		return AnnotationsParser::getAll($reflection);
	}

	/**
	 * @param stdClass
	 * @param string
	 * @return string|NULL
	 */
	private function defaultClassFallback(stdClass $r, $class)
	{
		if ($r->defaultClassFallback)
		{
			$defaultClass = call_user_func($r->defaultClassFallback, $class);
			if (class_exists($defaultClass))
			{
				return $defaultClass;
			}
		}
		return NULL;
	}

	/**
	 * @param stdClass
	 * @param string|false
	 * @return string|false
	 */
	private function getByClassName(stdClass $r, $class)
	{
		if (!$class)
		{
			return NULL;
		}
		if (!isset($r->cache[$class]))
		{
			$r->cache[$class] = false;
			$reflection = new ReflectionClass($class);
			$defaultClass = NULL;
			if ($reflection AND $reflection->implementsInterface($r->interface))
			{
				if ($reflection->isInstantiable())
				{
					if ($dc = $this->defaultClassFallback($r, $class))
					{
						$dcReflection = new \ReflectionClass($dc);
						if ($dcReflection->isInstantiable())
						{
							$defaultClass = $dcReflection->getName();
						}
					}
				}
				$r->cache[$class] = $this->getByReflection($r, $reflection, $defaultClass);
			}
		}
		return $r->cache[$class];
	}

	/**
	 * @param stdClass
	 * @param string
	 * @return string|false
	 * @throws AnnotationClassParserException
	 * @throws AnnotationClassParserMorePossibleClassesException
	 */
	private function getByReflection(stdClass $r, ReflectionClass $reflection, $defaultClass)
	{
		$annotation = $this->getAnnotations($reflection);
		if (isset($annotation[$r->annotation]))
		{
			if (count($annotation[$r->annotation]) !== 1)
			{
				throw new AnnotationClassParserException('Cannot redeclare ' . $reflection->getName() . '::@' . $r->annotation);
			}
			$class = $annotation[$r->annotation][0];
			if ($class === false)
			{
				$defaultClass = NULL;
			}
			else
			{
				if (!is_string($class))
				{
					$tmp = gettype($class);
					throw new AnnotationClassParserException($reflection->getName() . "::@{$r->annotation} expected class name, $tmp given");
				}
				if (PHP_VERSION_ID >= 50300 AND ($ns = $reflection->getNamespaceName()) !== '' AND class_exists($ns . '\\' . $class))
				{
					$class = $ns . '\\' . $class;
				}
				else if (!class_exists($class))
				{
					throw new AnnotationClassParserException($reflection->getName() . "::@{$r->annotation} class '$class' not exists");
				}
				if ($defaultClass AND strcasecmp($class, $defaultClass) !== 0)
				{
					throw new AnnotationClassParserMorePossibleClassesException('Exists annotation ' . $reflection->getName() . '::@' . $r->annotation . " and fallback '$defaultClass'");
				}
				return $class;
			}
		}
		if ($defaultClass)
		{
			return $defaultClass;
		}

		return $this->getByClassName($r, get_parent_class($reflection->getName()));
	}

}