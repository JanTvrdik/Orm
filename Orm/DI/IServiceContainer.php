<?php

namespace Orm;

/** DI Container */
interface IServiceContainer
{

	/**
	 * <pre>
	 * 	$c->addService('foo', function (Orm\IServiceContainer $c) { return new Foo; });
	 * 	$c->addService('foo', 'Foo');
	 * 	$c->addService('foo', new Foo);
	 * </pre>
	 *
	 * @param string
	 * @param Callback|Closure|string|Object class name, callback or object
	 * @return IServiceContainer
	 * @throws ServiceAlreadyExistsException
	 */
	public function addService($name, $service);

	/**
	 * @param string
	 * @param string|NULL
	 * @return Object
	 * @throws ServiceNotFoundException
	 * @throws InvalidServiceFactoryException
	 * @throws ServiceNotInstanceOfException if $instanceof not match with service
	 */
	public function getService($name, $instanceof = NULL);

	/**
	 * @param string
	 * @return Object
	 * @throws ServiceNotFoundException
	 */
	public function removeService($name);

	/**
	 * @param string
	 * @param bool
	 * @return bool
	 * @throws ServiceNotFoundException if $throw is true
	 */
	public function hasService($name, $throw = false);

}
