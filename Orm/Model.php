<?php

require_once dirname(__FILE__) . '/Entity/Entity.php';

require_once dirname(__FILE__) . '/Repository/Repository.php';

require_once dirname(__FILE__) . '/Mappers/Mapper.php';

/**
 * Kolekce Repository.
 * Stara se o jejich vytvareni.
 * Je to vstupni bod do modelu z jinych casti aplikace.

 * Na repository se pristupuje jako k property:
 * <pre>
 *
 * $orm = new RepositoryContainer;
 *
 * $orm->articles; // instanceof ArticlesRepository
 *
 * $article = $orm->articles->getById(1); // instanceof Article
 *
 * </pre>
 *
 * Do aplikace orm vsunete napriklad takto:
 * <pre>
 * // config.ini
 * service.orm = RepositoryContainer
 *
 * // BasePresenter.php
 * /**
 *  * @property-read RepositoryContainer $orm
 *  *⁄
 * abstract class BasePresenter extends Presenter
 * {
 * 	/** @var RepositoryContainer *⁄
 * 	private $orm;
 *
 *  /** @return RepositoryContainer *⁄
 * 	public function getOrm()
 * 	{
 * 		if (!isset($this->orm))
 * 		{
 * 			$this->orm = $this->context->getService('orm');
 * 		}
 * 		return $this->orm;
 * 	}
 * }
 * </pre>
 */
class RepositoryContainer extends Object
{
	/** @var RepositoryContainer @deprecated @todo di @see self::get() */
	static private $instance;

	/** @var array repositoryName => IRepository */
	private $repositories = array();

	public function __construct()
	{
		self::$instance = $this;
	}

	/**
	 * Vraci posledni vytvoreny container, je pro zpetnou kompatibilitu.
	 * A zatim jeste neni uplne vymysleno jak se bez toho obejit.
	 * Bohuzel zatim pouziva: Entity::getModel(), RelationshipLoader, MetaDataProperty::setOneToOne()
	 * @return RepositoryContainer
	 * @todo di
	 * @deprecated
	 */
	public static function get()
	{
		if (!isset(self::$instance))
		{
			throw new InvalidStateException();
		}
		return self::$instance;
	}

	/**
	 * Dej mi instanci repository.
	 * @var string repositoryName
	 * @return Repository |IRepository
	 */
	public function getRepository($name)
	{
		$name = strtolower($name);
		if (!isset($this->repositories[$name]))
		{
			$class = $this->getRepositoryClass($name);
			$this->checkRepositoryClass($class, $name);
			$this->repositories[$name] = new $class($this);
		}
		return $this->repositories[$name];
	}

	/**
	 * Existuje repository pod timto nazvem?
	 * @param string repositoryName
	 * @return bool
	 */
	final public function isRepository($name)
	{
		$name = strtolower($name);
		if (isset($this->repositories[$name])) return true;
		return $this->checkRepositoryClass($this->getRepositoryClass($name), $name, false);
	}

	/**
	 * Je tato trida repository?
	 * @param string repositoryClass
	 * @param string repositoryName
	 * @return true or throw exception
	 * @throws InvalidStateException
	 */
	final private function checkRepositoryClass($class, $name, $throw = true)
	{
		if (!class_exists($class))
		{
			if (!$throw) return false;
			throw new InvalidStateException("Repository '{$name}' doesn't exists");
		}

		$reflection = new ClassReflection($class);

		if (!$reflection->implementsInterface('IRepository'))
		{
			if (!$throw) return false;
			throw new InvalidStateException("Repository '{$name}' must implement IRepository");
		}
		else if ($reflection->isAbstract())
		{
			if (!$throw) return false;
			throw new InvalidStateException("Repository '{$name}' is abstract.");
		}
		else if (!$reflection->isInstantiable())
		{
			if (!$throw) return false;
			throw new InvalidStateException("Repository '{$name}' isn't instantiable");
		}

		return true;
	}

	/**
	 * repositoryName > repositoryClass
	 * @param string repositoryName
	 * @return string repositoryClass
	 */
	protected function getRepositoryClass($name)
	{
		if (!$name) return NULL;
		$class = $name . 'Repository';
		$class[0] = strtoupper($class[0]);
		return $class;
	}

	/**
	 * <pre>
	 * $orm->articles;
	 * </pre>
	 * Do not call directly.
	 * @param string repositoryName
	 * @return IRepository
	 */
	public function & __get($name)
	{
		$r = $this->getRepository($name);
		return $r;
	}

	/**
	 * Promitne vsechny zmeny do uloziste na vsech repository.
	 * @return void
	 * @see IRepository::flush()
	 * @see IMapper::flush()
	 */
	public function flush()
	{
		foreach ($this->repositories as $repo)
		{
			$repo->flush(true);
		}
	}

	/**
	 * Zrusi vsechny zmeny na vsech repository, ale do ukonceni scriptu se zmeny porad drzi.
	 * @todo zrusit i zmeny na entitach, aby se hned vratili do puvodniho stavu.
	 * @return void
	 * @see IRepository::clean()
	 * @see IMapper::clean()
	 */
	public function clean()
	{
		foreach ($this->repositories as $repo)
		{
			$repo->clean(true);
		}
	}

}

/** @deprecated */
abstract class RepositoriesCollection extends RepositoryContainer {}
/** @deprecated */
abstract class AbstractModel extends RepositoriesCollection {}
