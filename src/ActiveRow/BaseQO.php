<?php

namespace  Murdej\ActiveRow;

abstract class BaseQO
{
	protected $repo;

	public $limitFrom = null;

	public $limitCount = null;

	public $fullCount = null;

	public $mode = null;

	public function __construct($repo)
	{
		$this->repo = $repo;
	}

	public function exec()
	{
		if ($this->limitCount || $this->limitFrom)
		{
			$this->mode = 'count';
			$q = $this->createQuery();
			$this->prepareQuery($q);

			$this->fullCount = $q->count();
		}

		$this->mode = 'count';
		$q = $this->createQuery();
		$this->prepareQuery($q);
		if (method_exists($this, 'setupLimit')) $this->setupLimit($q);

		return $q;
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
