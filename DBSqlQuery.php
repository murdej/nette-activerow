<?php

namespace  Murdej\DataMapper;

class DBSqlQuery extends DBCollection
{
	protected $repo;
	
	protected $vars = [];
	
	protected $query = "";
	
	public function getSelection()
	{
		return $this->exec();
	}
	
	public function __construct(DBRepository $repo)
	{
		$this->repo = $repo;
	}
	
	public function value($val)
	{
		$this->query .= '?';
		$this->vars[] = $val;
		return $this;
	}
	
	public function code($code)
	{
		$this->query .= $code;
		return $this;
	}
	
	public function c($code) { return $this->code($code); }
	
	public function v($value) { return $this->value($value); }
	
	public function exec()
	{
		try
		{
			return call_user_func_array(
				array($this->repo->db, 'query'), 
				array_merge(array($this->query), $this->vars)
			);
		} 
		catch(\Nette\InvalidArgumentException $exc)
		{
			dump($this->query, $this->vars);
			throw $exc;
		}
	}
}