<?php
/**
 * Orm
 * @author Petr Procházka (petr@petrp.cz)
 * @license "New" BSD License
 */

namespace Orm;

use Dibi;

/** DI Container Factory */
class ServiceContainerFactory extends Object implements IServiceContainerFactory
{
	/** @var IServiceContainer */
	private $container;

	/** @param IServiceContainer|NULL */
	public function __construct(IServiceContainer $container = NULL)
	{
		if (!$container) $container = new ServiceContainer;
		$container->addService('annotationClassParser', 'Orm\AnnotationClassParser');
		$container->addService('mapperFactory', array($this, 'createMapperFactory'));
		$container->addService('repositoryHelper', 'Orm\RepositoryHelper');
		$container->addService('dibi', array($this, 'createDibi'));
		if ($performanceHelperCache = $this->getPerformanceHelperCacheFactory())
		{
			$container->addService('performanceHelperCache', $performanceHelperCache);
		}
		$this->container = $container;
	}

	/** @return IServiceContainer */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * @param IServiceContainer
	 * @return IMapperFactory
	 */
	public function createMapperFactory(IServiceContainer $container)
	{
		return new MapperFactory($container->getService('annotationClassParser', 'Orm\AnnotationClassParser'));
	}

	/** @return DibiConnection */
	public function createDibi()
	{
		return dibi::getConnection();
	}

	/** @return Closure */
	protected function getPerformanceHelperCacheFactory()
	{
		foreach (array('Nette\Environment', 'NEnvironment', 'Environment') as $class)
		{
			if (class_exists($class))
			{
				return function () use ($class) { return call_user_func(array($class, 'getCache'), 'Orm\PerformanceHelper'); };
			}	// @codeCoverageIgnoreStart
		}
	}			// @codeCoverageIgnoreEnd

}
