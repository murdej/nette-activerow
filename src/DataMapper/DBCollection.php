<?php

namespace  Murdej\DataMapper;

class DBCollection extends \Nette\Object implements \Iterator, \ArrayAccess, \Countable
{
	public $selection;
	
	function rewind() 
	{
		return $this->selection->rewind();
	}

	function current() 
	{
		return $this->repository->createEntity($this->selection->current());
	}

	function key() 
	{
		return $this->selection->key();
	}

	function next() 
	{
		return $this->selection->next();
	}

	function valid() 
	{
		return $this->selection->valid();
	}
	
	public function offsetSet($offset, $value)
	{
		return $this->selection->offsetSet($offset, $value);
	}

	public function offsetExists($offset)
	{
		return $this->selection->offsetExists($offset);
	}

	public function offsetUnset($offset)
	{
		return $this->selection->offsetUnset($offset);
	}

	public function offsetGet($offset) 
	{
		return $this->repository->createEntity($this->selection->offsetGet($offset));
	}

	public function count() 
	{
		return $this->selection->count();
	}
	
	public function exists()
	{
		return count($this->selection) > 0;
	}
}
