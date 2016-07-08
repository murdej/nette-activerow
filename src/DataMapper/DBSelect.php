<?php

namespace  Murdej\DataMapper;

class DBSelect extends DBCollection// \Nette\Object implements \Iterator, \ArrayAccess, \Countable
{
	public $selection;
	
	protected $repository;
	
	function __construct($repo, $selection = null)
	{
		$this->repository = $repo;
		$this->selection = $selection ? $selection : $repo->newTable();
		$order = $this->repository->tableInfo->defaultOrder;
		if ($order) $this->order($order);
	}
		
	public function order($columns)
	{
		$this->selection->order($columns);
		return $this;
	}
	
	public function where()
	{
		call_user_func_array([$this->selection, 'where'], func_get_args());
		return $this;
	}
	
	public function limit($limit, $offset = null)
	{
		$this->selection->limit($limit, $offset);
	}
	
	public function toArray()
	{
		$res = [];
		foreach($this as $i => $el) $res[$i] = $el;
		
		return $res;
	}
}
