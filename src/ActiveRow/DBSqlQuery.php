<?php

namespace  Murdej\ActiveRow;

class DBSqlQuery extends DBCollection
{
	protected $repo;
	
	protected $vars = [];
	
	protected $query = "";

	protected $_selection = null;
	
	public function getSelection()
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
	
	public function value($val)
	{
		$this->query .= '? ';
		$this->vars[] = $val;
		return $this;
	}

	public function values($vals, $sep = ', ')
	{
		$f = true;
		foreach ($vals as $val) {
			if ($f) $f = false;
			else $this->query .= $sep;
			$this->query .= '? ';
			$this->vars[] = $val;		
		}
		return $this;
	}	
	
	public function code($code, ...$vars)
	{
		$this->query .= $code;
		$this->vars = array_merge($this->vars, $vars);
		return $this;
	}
	
	public function identifier($code)
	{
		//todo: escape identifier name
		$this->query .= $code;
		return $this;
	}
	
	public function c($code, ...$vars) { return $this->code($code, ...$vars); }
	
	public function i($code) { return $this->identifier($code); }

	public function v($value) { return $this->value($value); }
	
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