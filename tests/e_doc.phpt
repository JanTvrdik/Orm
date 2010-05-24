<?php

require dirname(__FILE__) . '/base.php';

/**
 * @property $readWrite
 * @property-read $read
 * @property-write $write
 */
class Test extends Entity
{

}

$t = new Test;

$t->readWrite = 'Lorem ipsum';
$t->write = 'Ipsum lorem';
try {
	$t->read = 'Xxxxx';
} catch (Exception $e) { dump($e, 'set read'); }

dump($t->readWrite, 'get readWrite');
try {
	dump($t->write, 'get write');
} catch (Exception $e) { dump($e, 'get write'); }
dump($t->read, 'get read');


dump(EntityManager::getEntityParams('Test'));

__halt_compiler();
------EXPECT------
set read: Exception MemberAccessException: Cannot assign to a read-only property Test::$read.

get readWrite: string(11) "Lorem ipsum"

get write: Exception MemberAccessException: Cannot assign to a write-only property Test::$write.

get read: NULL

array(4) {
	"id" => array(3) {
		"types" => array(1) {
			0 => string(3) "int"
		}
		"get" => array(1) {
			"method" => NULL
		}
		"since" => string(6) "Entity"
	}
	"readWrite" => array(4) {
		"types" => array(0)
		"get" => array(1) {
			"method" => NULL
		}
		"since" => string(4) "Test"
		"set" => array(1) {
			"method" => NULL
		}
	}
	"read" => array(3) {
		"types" => array(0)
		"get" => array(1) {
			"method" => NULL
		}
		"since" => string(4) "Test"
	}
	"write" => array(3) {
		"types" => array(0)
		"set" => array(1) {
			"method" => NULL
		}
		"since" => string(4) "Test"
	}
}
