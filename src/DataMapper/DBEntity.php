<?php

namespace  Murdej\DataMapper;

use Nette\Reflection\ClassType;

class DBEntity extends \Nette\Object
{

	public $src;
	
	public $entity;
	
	public $modified = array();
	
	public $converted = array();
	
	public $defaults = [];
	
	public function get($col)
	{
		$dbi = $this->getDbInfo();
		// properties
		if ($dbi->existsCol($col))
		{
			if (!array_key_exists($col, $this->converted))
			{
				$this->converted[$col] = $this->convertFromSrc($col);
			}
			return $this->converted[$col];
		}
		else if ($dbi->existsRelated($col))
		{
			$ri = $dbi->relateds[$col];
			$sel = new DBSelect(
				new DBRepository($ri->relClass),
				$this->src->related($ri->relTableName, $ri->relColumn)
			);
			return $sel;
		}
		else 
		{
			$reflexion = new ClassType(get_class($this->entity));
			$uname = ucfirst($col);
			do
			{
				$methodName = 'get' . $uname;
				if ($reflexion->hasMethod($methodName)) break;

				$methodName = 'is' . $uname;
				if ($reflexion->hasMethod($methodName)) break;
				
				dump($dbi);
				throw new \Exception("Property $col is not defined.");
			} while(false);
			return $this->entity->$methodName();
		}
		
	}
	
	public function set($col, $value)
	{
		$dbi = $this->getDbInfo();
		if ($dbi->existsCol($col))
		{
			$colDef = $dbi->columns[$col];
			if ($colDef->fkClass && $col == $colDef->propertyName)
				throw new \Exception("Cannot replace fk object $col.");
			if (!array_key_exists($col, $this->converted) || $this->converted[$col] != $value)
			{
				$this->converted[$col] = $value;
				if (!in_array($col, $this->modified)) $this->modified[] = $col;
			}
		}
		else 
		{
			$reflexion = new ClassType(get_class($this->entity));
			$uname = ucfirst($col);
			
			$methodName = 'set' . $uname;
			if (!$reflexion->getMethod($methodName)) throw new \Exception("Column $col is not defined.");

			$this->entity->$methodName($value);
		}
		// else throw new \Exception("Column $col is not defined.");
	}
	
	public function getDb()
	{
		return DBRepository::getDatabase($this->database);
	}
	
	public function getDbInfo($className = null) 
	{
		if (!$className) $className = get_class($this->entity);
		return TableInfo::get($className);
	}
	
	public function convertFromSrc($col)
	{
		$val = (!is_array($this->src) || array_key_exists($col, $this->src))
			? $this->src[$col]
			: (array_key_exists($col, $this->defaults)
				? $this->defaults[$col]
				: null
			);
		
		return Converter::get()->convertTo($val, $this->dbInfo->columns[$col], $col);
	}

	public function __construct($entity, $src = [])
	{
		$this->entity = $entity;
		$this->src = $src;
		$this->defaults = &$this->getDbInfo()->defaults;
	}
	
	public function getModifiedDbData($forInsert = false)
	{
		$res = [];
		// přímo modifikované
		foreach($this->modified as $col)
		{
			$colInfo = $this->dbInfo->columns[$col];
			$res[$colInfo->columnName] = Converter::get()->convertFrom($this->converted[$col], $this->dbInfo->columns[$col]);
		}
		foreach($this->dbInfo->columns as $col => $colInfo)
		{
			// serializované
			if ($colInfo->serialize && array_key_exists($col, $this->converted))
			{
				$dbValue = Converter::get()->convertFrom($this->converted[$col], $colInfo);
				if (!array_key_exists($col, $this->src) || $dbValue != $this->src[$col])
					$res[$colInfo->columnName] = $dbValue;
			}
			if ($forInsert)
			{
				// Pro insert i default hodnoty
				if ($colInfo->defaultValue && !in_array($col, $this->modified))
				{
					if (!array_key_exists($col, $this->converted)) $this->get($col);
					$res[$colInfo->columnName] = Converter::get()->convertFrom($this->converted[$col], $this->dbInfo->columns[$col]);
				}
			}
		}
		
		return $res;
	}
	
	public function save()
	{
		if (is_array($this->src))
		{
			// Nový záznam
			$this->src = DBRepository::getDatabase(null)
				->table($this->dbInfo->tableName)
				->insert($this->getModifiedDbData(true));
		}
		else if (get_class($this->src) == 'Nette\\Database\\Table\\ActiveRow')
		{
			$this->src->update($this->getModifiedDbData(false));
			$this->converted = [];
			$this->modified = [];
		} else {
			throw new \Exception("Can not use Save");
		}
	}
	
	public function toArray($cols = null, $fkObjects = false)
	{
		$dbi = $this->dbInfo;
		if (!$cols) $cols = array_keys($dbi->columns);
		$res = [];
		foreach($cols as $col)
		{
			$colDef = $dbi->columns[$col];
			if (!$fkObjects && $colDef->fkClass && $col == $colDef->propertyName) continue;
			$res[$col] = $this->get($col);
		}
		
		return $res;
	}
}
