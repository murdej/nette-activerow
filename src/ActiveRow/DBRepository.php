<?php

namespace  Murdej\ActiveRow;

use App\Repositories\DBRepos;
use Nette\Database\Explorer;
use Nette\SmartObject;
use PHPStan\Type\CallableType;

/**
 * @template T
 * @property TableInfo $tableInfo
 */
class DBRepository extends \Nette\NObject
{
	// use SmartObject;

	/** @var Nette\Database\Context */
	public $database = null;
	
	public $className = null;

	/**
	 * @param class-string<T> $cn
	 * @param Explorer|null $db
	 */
	function __construct($cn, $db = null)
	{
		$this->className = $cn;
		$this->database = $db;
	}

	/**
	 * @var callable
	 */
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
	
	public function getTableInfo() : TableInfo
	{
		return TableInfo::get($this->className);
	}

	/**
	 * @param null $tableName
	 * @return mixed
	 */
	public function newTable($tableName = null)
	{
		if (!$tableName)
		{
			$tableInfo = $this->tableInfo;
			$tableName = $tableInfo->tableName;
		} 
		return $this->getDb()->table($tableName);
	}

	/**
	 * @param array $params
	 * @return T|null
	 */
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
		$sel->select('`'.$this->tableInfo->tableName.'`.*');
		$row = $sel->fetch();
		// dump($row);
		if ($row == null) return null;
		
		return $this->createEntity($row);
	}

	/**
	 * @param $pk
	 * @return T|null
	 */
	public function get($pk)
	{
		$dbr = $this->newTable()->select('*')->get($pk);
		return $dbr ? $this->createEntity($dbr) : null;
	}

	/**
	 * @param array $row
	 * @return T
	 */
	public function createEntity($row = [])
	{
		$className = $this->className;
		
		return new $className($row, $this->database);
	}

	/**
	 * @return T
	 */
	public function newEntity()
	{
		$className = $this->className;

		return new $className([], $this->database);
	}

	/**
	 * @return DBSelect
	 */
	public function newSelect()
	{
		return new DBSelect($this);
	}

	/**
	 * @return DBSqlQuery
	 */
	public function newSqlQuery() : DBSqlQuery
	{
		return new DBSqlQuery($this);
	}

	/**
	 * @param $query
	 * @param ...$params
	 * @return DBSqlQuery
	 */
	public function query($query, ...$params)
	{
		return new DBSqlQuery($this, $this->db->query($query, ...$params)); 
	}

	public function nmRelSave(string $table, $keyName, $keyValue, string $itemName, $itemValues, string $op = 'both', array $extraInsertData = []) : array
	{
		$res = ['inserted' => [], 'deleted' => []];
		$keyWhere = function($q) use ($keyName, $keyValue)
		{
			if (is_array($keyName))
			{
				foreach($keyName as $k => $v)
					$q->where($k, $v);
			} 
			else
			{
				$q->where($keyName, $keyValue);
			}	
		};
		
		foreach ($itemValues as $i => $value) 
		{
			if ($value == '') $itemValues[$i] = null;
		}
		$q = $this->db->table($table)
			->select($itemName);
		
		$keyWhere($q);

		$curValues = $q->fetchPairs(null, $itemName);
		// Delete
		$deleted = array_unique(array_diff($curValues, $itemValues));
		if ($deleted && $op != 'add')
		{
			$res['deleted'] = $deleted;
			$q = $this->db->table($table);
			$keyWhere($q);
			$itemCond = [$itemName => $deleted];
			if (in_array(null, $deleted))
				$itemCond[] = "$itemName IS NULL";
			$q->whereOr($itemCond);
			$q->delete();
		}
		$inserted = array_unique(array_diff($itemValues, $curValues));
		if ($inserted && $op != 'drop')
		{
			$res['inserted'] = $inserted;
			$data = [];
			foreach($inserted as $item)
			{
				$rowData = [
					$itemName => $item
				] + $extraInsertData;

				if (is_array($keyName))
				{
					$rowData += $keyName;
				}
				else
				{
					$rowData[$keyName] = $keyValue;
				}
				$data[] = $rowData;
			} 
			$this->db->query('INSERT INTO '.$table, $data);
		}

		return $res;
	} 

	public function nmRelLoad($table, $keyName, $keyValue, $itemName)
	{
		$q = $this->db->table($table)->select($itemName);
		
		if (is_array($keyName))
		{
			foreach($keyName as $k => $v)
				$q->where($k, $v);
		} 
		else
		{
			$q->where($keyName, $keyValue);
		}	

		return $q->fetchPairs(null, $itemName);
	} 
}
