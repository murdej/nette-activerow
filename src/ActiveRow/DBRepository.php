<?php

namespace  Murdej\ActiveRow;

class DBRepository extends \Nette\Object
{
	/** @var Nette\Database\Context */
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
		if (!$db) 
		{
			if (self::$databaseCreator)
			{
				$fn = self::$databaseCreator;
				$db = $fn($tableInfo);
			} else throw new \Exception("You need to pass \$database in the constructor or set factory to DBRepository::\$databaseCreator");
		}
		if ($db instanceof \Nette\Database\Context) return $db;
		else throw new \Exception("\$database is not instance of Nette\Database\Context.");
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
			if (is_int($k))
				$sel->where($v);
			else
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

	public function query($query, ...$params)
	{
		return new DBSqlQuery($this, $this->db->query($query, ...$params)); 
	}

	public function nmRelSave($table, $keyName, $keyValue, $itemName, $itemValues)
	{
		foreach ($itemValues as $i => $value) 
		{
			if ($value == '') $itemValues[$i] = null;
		}
		$curValues = $this->db->table($table)
			->select($itemName)
			->where($keyName, $keyValue)
			->fetchPairs(null, $itemName);
		// Delete
		$deleted = array_diff($curValues, $itemValues);
		if ($deleted)
			$this->db->table($table)
				->where($keyName, $keyValue)
				->where($itemName, $deleted)
				->delete();
		$inserted = array_diff($itemValues, $curValues);
		if ($inserted)
		{
			$data = [];
			foreach($inserted as $item) $data[] = [
				$keyName => $keyValue, 
				$itemName => $item
			];
			$this->db->query('INSERT INTO '.$table, $data);
		}
	} 

	public function nmRelLoad($table, $keyName, $keyValue, $itemName)
	{
		return $this->db->table($table)
			->select($itemName)
			->where($keyName, $keyValue)
			->fetchPairs(null, $itemName);
	} 
}
