<?php

namespace  Murdej\DataMapper;

class DBRepository extends \Nette\Object
{
	/** @var NdbContext */
	public $database = null;
	
	public $className = null;
	
	function __construct($cn, $db = null)
	{
		$this->className = $cn;
		$this->database = $db;
	}
	
	public static $databaseCreator = null;
	
	public function getDb()
	{
		return self::getDatabase($this->database, TableInfo::get($this->className));
	}
	
	public static function getDatabase($db, $tableInfo)
	{
		if ($db) return $db;
		if (self::$databaseCreator)
		{
			$fn = self::$databaseCreator;
			return $fn($tableInfo);
		}
		throw new \Exception("You need to pass \$database in the constructor or set factory to DBRepository::\$databaseCreator");
	}
	
	public function getTableInfo()
	{
		return TableInfo::get($this->className);
	}
	
	public function newTable()
	{
		return $this->getDb()->table($this->tableInfo->tableName);
	}
	
	public function getBy($params)
	{
		$sel = $this->newTable();
		foreach($params as $k => $v)
		{
			$sel->where($k, $v);
		}
		$sel->limit(1);
		// $sel->select('*');
		$row = $sel->fetch();
		// dump($row);
		if ($row == null) return null;
		
		return $this->createEntity($row);
	}

	public function get($pk)
	{
		$dbr = $this->newTable()->get($pk);
		return $dbr ? $this->createEntity($dbr) : null;
	}
	
	public function createEntity($row)
	{
		$className = $this->className;
		
		return new $className($row, $this->database);
	}

	public function newSelect()
	{
		return new DBSelect($this);
	}

	public function newSqlQuery()
	{
		return new DBSqlQuery($this);
	}
}
