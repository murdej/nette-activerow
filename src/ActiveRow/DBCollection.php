<?php

namespace  Murdej\ActiveRow;

use Nette;
use Nette\SmartObject;

/**
 * @template T
 */
class DBCollection /*extends \Nette\Object*/ implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
	use SmartObject;

	public $selection;

	/**
	 * @var DBRepository
	 */
	public $repository;

	#[\ReturnTypeWillChange]
	function rewind() 
	{
		return $this->getSelection()->rewind();
	}

	#[\ReturnTypeWillChange]
	function current()
	{
		return $this->repository->createEntity($this->getSelection()->current());
	}

	#[\ReturnTypeWillChange]
	function key()
	{
		return $this->getSelection()->key();
	}

	#[\ReturnTypeWillChange]
	function next()
	{
		return $this->getSelection()->next();
	}

	#[\ReturnTypeWillChange]
	function valid()
	{
		return $this->getSelection()->valid();
	}

	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		return $this->getSelection()->offsetSet($offset, $value);
	}

	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return $this->getSelection()->offsetExists($offset);
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		return $this->getSelection()->offsetUnset($offset);
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->repository->createEntity($this->getSelection()->offsetGet($offset));
	}

	#[\ReturnTypeWillChange]
	public function count()
	{
		return $this->getSelection()->count();
	}

	#[\ReturnTypeWillChange]
	public function exists()
	{
		return count($this->getSelection()) > 0;
	}

    /**
     * @return ?T
     */
	public function fetch()
	{
		$row = $this->getSelection()->fetch();
		return $row ? $this->repository->createEntity($row) : null;
	}

    /**
     * @param $key
     * @param $value
     * @return array<mixed,mixed|T>
     * @throws \Exception
     */
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
			if ($rowIsArray) {
                $r = [];
                foreach($row as $k => $v) $r[$k] = $v;
                $row = $r;
            }
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

	public function jsonSerialize(): mixed
	{
		return $this->toArray();
	}
}
