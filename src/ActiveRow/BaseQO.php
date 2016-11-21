<?php

namespace  Murdej\DataMapper;

abstract class BaseQO
{
	protected $repo;

	public function __construct($repo)
	{
		$this->repo = $repo;
	}

	public function exec()
	{
		$q = $this->createQuery();
		$this->prepareQuery($q);

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
}
