<?php

namespace  Murdej\ActiveRow;

use Nette\Database\ResultSet;
use Nette\Database\Table\Selection;

class DBSqlQuery extends DBCollection
{
    use TSqlCodeComposer;

	protected $repo;

	protected $_selection = null;
	
	public function getSelection(): Selection|ResultSet
	{
		if ($this->_selection === null)
			$this->_selection = $this->exec();

		return $this->_selection;
	}
	
	public function __construct(DBRepository $repo, $selection = null)
	{
		$this->repository = $repo;
		$this->_selection = $selection;
	}

	public function exec()
	{
		try
		{
			$res = call_user_func_array(
				array($this->repository->db, 'query'), 
				array_merge(array($this->query), $this->vars)
			);

			return $res;
		} 
		catch(\Nette\InvalidArgumentException $exc)
		{
			dump($this->query, $this->vars);
			throw $exc;
		}
	}

	public function getAsRows()
	{
		return $this->exec();
	}
}