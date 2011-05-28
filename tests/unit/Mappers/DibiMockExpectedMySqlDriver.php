<?php

use Nette\NotSupportedException;

class DibiMockExpectedMySqlDriver extends DibiMockEscapeMySqlDriver
{
	private $expected = array();

	public function addExpected($function, $result)
	{
		$args = func_get_args();
		$function = array_shift($args);
		$result = array_shift($args);
		$this->expected[] = (object) array(
			'function' => $function,
			'result' => $result,
			'args' => $args,
		);
	}

	private function expected($function)
	{
		$args = func_get_args();
		$function = array_shift($args);
		if ($this->expected)
		{
			$expected = current($this->expected);
			unset($this->expected[key($this->expected)]);
			PHPUnit_Framework_Assert::assertSame($expected->function, $function, "$function is not expected, expected $expected->function");
			PHPUnit_Framework_Assert::assertSame($expected->args, $args, "$function are diferent");
			return $expected->result;
		}
		PHPUnit_Framework_Assert::fail("$function is not expected");
	}

	public function query($sql)
	{
		$e = $this->expected(__FUNCTION__, trim(preg_replace('#\s+#', ' ', $sql)));
		if ($e)
		{
			return $this->createResultDriver($e);
		}
	}

	public function getInfo()
	{
		throw new NotSupportedException;
	}

	public function getAffectedRows()
	{
		throw new NotSupportedException;
	}

	public function getInsertId($sequence)
	{
		throw new NotSupportedException;
	}

	public function begin($savepoint = NULL)
	{
		throw new NotSupportedException;
	}

	public function commit($savepoint = NULL)
	{
		throw new NotSupportedException;
	}

	public function rollback($savepoint = NULL)
	{
		throw new NotSupportedException;
	}

	public function getResource()
	{
		throw new NotSupportedException;
	}

	public function getReflector()
	{
		throw new NotSupportedException;
	}

	public function createResultDriver($resource)
	{
		$this->expected(__FUNCTION__, $resource);
		return $this;
	}

	public function getRowCount()
	{
		return $this->expected(__FUNCTION__);
	}

	public function fetch($assoc)
	{
		return $this->expected(__FUNCTION__, $assoc);
	}

	public function seek($row)
	{
		return $this->expected(__FUNCTION__, $row);
	}

	public function free()
	{
		throw new NotSupportedException;
	}

	public function getResultColumns()
	{
		throw new NotSupportedException;
	}

	public function getResultResource()
	{
		throw new NotSupportedException;
	}

	public function disconnect()
	{
		if ($this->expected)
		{
			$f = current($this->expected)->function;
			$e = $this->expected;
			$this->expected = array();
			PHPUnit_Framework_Assert::assertEmpty($e, "$f is expected");
		}
	}

	public function __clone()
	{
		throw new InvalidStateException;
	}

}
