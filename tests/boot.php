<?php

define('ORM_DIR', dirname(__FILE__) . '/../Orm');
define('LIBS_DIR', dirname(__FILE__) . '/libs');
define('TMP_DIR', dirname(__FILE__) . '/tmp');

require_once LIBS_DIR . '/Nette/loader.php';
require_once LIBS_DIR . '/dump.php';
require_once LIBS_DIR . '/dibi/dibi.php';
require_once ORM_DIR . '/loader.php';

Debug::enable(false);
Debug::$strictMode = true;

date_default_timezone_set('Europe/Prague');

Environment::setVariable('tempDir', TMP_DIR);

$r = new RobotLoader;
$r->setCacheStorage(Environment::getService('Nette\\Caching\\ICacheStorage'));
$r->addDirectory(LIBS_DIR);
$r->addDirectory(dirname(__FILE__) . '/unit');
$r->register();

require_once ORM_DIR . '/Mappers/Collection/DataSourceCollection.php';

abstract class TestCase extends PHPUnit_Framework_TestCase
{
	public function assertException(Exception $e, $type, $message)
	{
		$this->assertEquals($type, get_class($e));
		$this->assertEquals($e->getMessage(), $message);
	}

	public static function readAttribute($classOrObject, $attributeName)
	{
		try {
			return parent::readAttribute($classOrObject, $attributeName);
		} catch (PHPUnit_Framework_ExpectationFailedException $e) {
			if (is_object($classOrObject) AND $e->getMessage() == 'Failed asserting that object of class "'.get_class($classOrObject).'" has attribute "'.$attributeName.'".')
			{
				foreach ((array) $classOrObject as $key => $value)
				{
					if (String::endsWith($key, "\0$attributeName"))
					{
						return $value;
					}
				}
			}
			throw $e;
		}
	}

}

class Model extends RepositoriesCollection
{

}

PerformanceHelper::$keyCallback = function () {
	return String::random(99);
};