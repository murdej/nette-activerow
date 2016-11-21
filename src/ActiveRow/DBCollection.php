<?php

namespace  Murdej\DataMapper;

class DBCollection extends \Nette\Object implements \Iterator, \ArrayAccess, \Countable
{
	public $selection;

	public $repository;
	
	function rewind() 
	{
		return $this->getSelection()->rewind();
	}

	function current() 
	{
		return $this->repository->createEntity($this->getSelection()->current());
	}

	function key() 
	{
		return $this->getSelection()->key();
	}

	function next() 
	{
		return $this->getSelection()->next();
	}

	function valid() 
	{
		return $this->getSelection()->valid();
	}
	
	public function offsetSet($offset, $value)
	{
		return $this->getSelection()->offsetSet($offset, $value);
	}

	public function offsetExists($offset)
	{
		return $this->getSelection()->offsetExists($offset);
	}

	public function offsetUnset($offset)
	{
		return $this->getSelection()->offsetUnset($offset);
	}

	public function offsetGet($offset) 
	{
		return $this->repository->createEntity($this->getSelection()->offsetGet($offset));
	}

	public function count() 
	{
		return $this->getSelection()->count();
	}
	
	public function exists()
	{
		return count($this->getSelection()) > 0;
	}

	public function fetch()
	{
		$row = $this->getSelection()->fetch();
		return $row ? $this->repository->createEntity($row) : null;
	}

	public function fetchPairs($key, $value = null)
	{
		$res = [];
		foreach($this as $row)
		{
			$k = $row->$k;
			$res[$k] = $value ? $row->$value : $row;
		}

		return $res;
	}
}
