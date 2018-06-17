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
		$sel->select('*');
		$row = $sel->fetch();
		// dump($row);
		if ($row == null) return null;
		
		return $this->createEntity($row);
	}

	public function get($pk)
	{
		$dbr = $this->newTable()->select('*')->get($pk);
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

	public function nmRelSave($table, $keyName, $keyValue, $itemName, $itemValues, $op = 'both', $extraInsertData = [])
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
		if ($deleted && $op != 'add')
		{
			$q = $this->db->table($table)
				->where($keyName, $keyValue);
			$itemCond = [$itemName => $deleted];
			if (in_array(null, $deleted))
				$itemCond[] = "$itemName IS NULL";
			$q->whereOr($itemCond);
			$q->delete();
		}
		$inserted = array_diff($itemValues, $curValues);
		if ($inserted && $op != 'drop')
		{
			$data = [];
			foreach($inserted as $item) $data[] = [
				$keyName => $keyValue, 
				$itemName => $item
			] + $extraInsertData;
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
