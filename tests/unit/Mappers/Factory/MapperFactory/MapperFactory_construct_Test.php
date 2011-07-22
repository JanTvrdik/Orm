<?php

use Orm\MapperFactory;
use Orm\AnnotationClassParser;

/**
 * @covers Orm\MapperFactory::__construct
 */
class MapperFactory_construct_Test extends TestCase
{

	public function testImplement()
	{
		$this->assertInstanceOf('Orm\IMapperFactory', new MapperFactory(new AnnotationClassParser));
	}

	public function test()
	{
		$p = new AnnotationClassParser;
		$this->assertArrayNotHasKey('mapper', $this->readAttribute($p, 'registered'));
		$f = new MapperFactory($p);
		$r = $this->readAttribute($p, 'registered');
		$this->assertArrayHasKey('mapper', $r);
		$r = (array) $r['mapper'];
		$this->assertSame(4, count($r));
		$this->assertSame('mapper', $r['annotation']);
		$this->assertSame('Orm\IRepository', $r['interface']);
		$this->assertSame(array($f, 'createDefaultMapperClass'), $r['defaultClassFallback']);
		$this->assertSame(array(), $r['cache']);
	}

}