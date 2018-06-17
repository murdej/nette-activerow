<?php

namespace  Murdej\ActiveRow;

abstract class BaseQO
{
	protected $repo;

	public $limitFrom = null;

	public $limitCount = null;

	public $fullCount = null;

	public $mode = null;

	public $countField = null;

	public function __construct($repo)
	{
		$this->repo = $repo;
	}

	public function exec($mode = null)
	{
		if (!$mode)
		{
			if ($this->limitCount || $this->limitFrom)
			{
				$this->mode = 'count';
				$q = $this->createQuery();
				$this->prepareQuery($q);
	
				if ($this->countField)
				{
					$this->fullCount = $q->getSelection()->fetch()[$this->countField];
				} 
				else
				{
					$this->fullCount = $q->count();
				}
			}
		}

		$this->mode = $mode ? $mode : 'data';
		$q = $this->createQuery();
		$this->prepareQuery($q);
		if (method_exists($this, 'setupLimit')) $this->setupLimit($q);

		return $q;
	}

	public function getRawRows($mode = null)
	{
		$res = [];
		foreach($this->exec($mode)->selection as $row)
		{
			$res[] = $row;
		}

		return $res;
	}

	public function fromArray($arr)
	{
		foreach($arr as $k => $v)
			$this->{$k} = $v;
	}	

	abstract protected function createQuery();

}

abstract class BaseQOQuery extends BaseQO
{
	abstract protected function prepareQuery($query);

	protected function createQuery()
	{
		return $this->repo->newSqlQuery();
	}
}

abstract class BaseQOSelect extends BaseQO
{
	abstract protected function prepareQuery($query);

	protected function createQuery()
	{
		return $this->repo->newSelect();
	}

	protected function setupLimit($q)
	{
		$q->limit($this->limitCount, $this->limitFrom);
	}	
}
