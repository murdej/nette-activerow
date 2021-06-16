<?php

namespace  Murdej\ActiveRow;

class DBSelect extends DBCollection// \Nette\Object implements \Iterator, \ArrayAccess, \Countable
{
	public $selection;
	
	function __construct($repo, $selection = null)
	{
		$this->repository = $repo;
		if ($selection === false)
			$this->selection =$repo->newTable()->where(['1 = 0']);
		else
			$this->selection = $selection ? $selection : $repo->newTable();
		// $order = $this->repository->tableInfo->defaultOrder;
		// if ($order) $this->order($order);
	}
		
	public function order($columns)
	{
		$this->selection->order($columns);
		return $this;
	}

	protected $prepared = false;

	public function getSelection()
	{
		if (!$this->prepared)
		{
			$this->selection->select('`'.$this->repository->tableInfo->tableName.'`.*');
			$order = $this->repository->tableInfo->defaultOrder;
			if ($order) $this->order($order);
			$this->prepared = true;
		}
		
		return $this->selection;
	}
	
	public function where()
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
		{
			foreach ($args[0] as $key => $value) 
			{
				if (is_int($key))
					$this->selection->where($value);
				else
					$this->selection->where($key, $value);
			}
		} else call_user_func_array([$this->selection, 'where'], $args);
		return $this;
	}

	public function group(...$params)
	{
		$this->selection->group(...$params);
		return $this;
	}

	public function whereOr(...$args)
	{
		$this->selection->whereOr(...$args);
		return $this;
		/*$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
		{
			foreach ($args[0] as $key => $value) 
			{
				if (is_int($key))
					$this->selection->where($value);
				else
					$this->selection->where([$key => $value]);
			}
		} else call_user_func_array([$this->selection, 'whereOr'], $args);
		return $this;*/
	}
	
	public function limit($limit, $offset = null)
	{
		$this->selection->limit($limit, $offset);
		return $this;
	}

	public function sum()
	{
		return call_user_func_array([$this->selection, 'sum'], func_get_args());
	}
	
	public function delete()
	{
		return call_user_func_array([$this->selection, 'delete'], func_get_args());
	}

	public function max()
	{
		return call_user_func_array([$this->selection, 'max'], func_get_args());
	}

	/* public function toArray()
	{
		$res = [];
		foreach($this as $i => $el) $res[$i] = $el;
		
		return $res;
	} */
	
	public function getAsRows()
	{
		return $this->selection;
	}

	public function alias(...$args) : DBSelect
	{
		$this->selection->alias(...$args);
		return $this;
	}

	public function joinWhere(...$args) : DBSelect
	{
		$this->selection->joinWhere(...$args);
		return $this;
	}
}
