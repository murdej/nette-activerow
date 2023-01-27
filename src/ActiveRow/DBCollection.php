<?php

namespace  Murdej\ActiveRow;

use Nette;
use Nette\SmartObject;

class DBCollection /*extends \Nette\Object*/ implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
	use SmartObject;

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
			/* if ($key)
			{
				$k = $row->$key;
				$res[$k] = $value ? $row->$value : $row;
			} else $res[] = $value ? $row->$value : $row; */
			if ($key)
			{
				$k = $this->getColValue($row, $key);
				$res[$k] = $value ? $this->getColValue($row, $value) : $row;
			} else $res[] = $value ? $this->getColValue($row, $value) : $row;
		}

		return $res;
	}

	public function fetchRawArray(bool $rowIsArray = false) : array
	{
		$res = [];
		foreach($this->getSelection() as $row)
		{
			if ($rowIsArray) $row = $row->toArray();
			/* $arow = [];
			if ($row) */
			$res[] = $row/*->toArray()*/;
		}

		return $res;
	}

    public function fetchArray(bool $rowIsArray = false): array
    {
        $res = [];
        foreach($this as $row)
        {
            if ($rowIsArray) $row = $row->toArray();
            $res[] = $row;
        }

        return $res;
    }

    protected function getColValue($row, $col)
	{
		if (is_string($col))
			return $row->$col;
		elseif (Nette\Utils\Callback::check($col)) 
			return Nette\Utils\Callback::invoke($col, $row);
		else
			throw new \Exception('Column must be string or callable');
	}

	public function fetchField($field = null)
	{
		return $field 
			? $this->getSelection()->fetchField($field)
			: $this->getSelection()->fetchField();
	}

	public function toArray() : array
	{
		$res = [];
		foreach ($this as $item) $res[] = $item;
		return $res;
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}
}
