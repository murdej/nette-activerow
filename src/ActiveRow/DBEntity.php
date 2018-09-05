<?php

namespace  Murdej\ActiveRow;

use Nette\Reflection\ClassType;

class DBEntity extends \Nette\Object
{

	public $src;
	
	public $entity;
	
	public $modified = array();
	
	public $converted = array();
	
	public $defaults = [];

	public $collection = null;
	
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
			if (is_array($this->src))
			{
				$items = false;
			} 
			else
			{
				// Háže tam nesmyslnou podmínku a vrátí h*vno
				//$items = $this->src->related($ri->relTableName, $ri->relColumn);
				// Pomalejší ale funkční
				$cn = $ri->relClass;
				$pkCol = reset($dbi->primary)->columnName;
				return $cn::findBy([(
					$ri->relColumn ?: $dbi->tableName.'Id'
				) => $this->get($pkCol)]);
			}
			$sel = new DBSelect(
				new DBRepository($ri->relClass),
				$items
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
				
				throw new \Exception("Property $reflexion->name::$col is not defined.");
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
			if ($colDef->blankNull && !$value) $value = null;
			if ($colDef->fkClass && $col == $colDef->propertyName)
				throw new \Exception("Cannot replace fk object $col.");
			if (!array_key_exists($col, $this->converted) || $this->converted[$col] != $value)
			{
				$this->converted[$col] = $value;
				if (!in_array($col, $this->modified)) $this->modified[] = $col;
				if ($colDef->fkClass)
					unset($this->converted[$colDef->propertyName]);
			}
		}
		else 
		{
			$reflexion = new ClassType(get_class($this->entity));
			$uname = ucfirst($col);
			
			$methodName = 'set' . $uname;
			if (!$reflexion->hasMethod($methodName)) throw new \Exception("Column '$col' is not defined in class '".$dbi->className."'.");

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
	
	public function isNew()
	{
		return is_array($this->src);
	}

	public function convertFromSrc($col)
	{
		$val = null;
		if (is_array($this->src))
		{
			if (array_key_exists($col, $this->src))
			{
				$val = $this->src[$col];
			}
			else
			{
				if (array_key_exists($col, $this->defaults))
				{
					$val = $this->defaults[$col];
				} else {
					$colDef = $this->getDbInfo()->columns[$col];
					// Volání FKO na novém objektu
					if ($colDef->fkClass && $col == $colDef->propertyName)
					{
						$className = $colDef->fkClass; 
						$val = $className::get($this->get($colDef->columnName));
						$val = $val ? $val->_dbEntity->src : null;
					} 
					else if (!$colDef->nullable)
					{
						// Default hodnoty nenull primitivních typů
						$val = Converter::get()->getDefaultOfType($colDef->type);
					}
				}
			}
		} 
		else 
		{
			if (!isset($this->src[$col]))
			// if (!isset($this->src->$col))
			{
				$colDef = $this->getDbInfo()->columns[$col];
				// Volání FKO na view
				if ($colDef->fkClass && $col == $colDef->propertyName)
				{
					$className = $colDef->fkClass; 
					$val = $className::get($this->get($colDef->columnName));
					$val = $val ? $val->_dbEntity->src : null;
				}
			} 
			else
			$val = $this->src->$col;
		}

		/* $val = (!is_array($this->src) || array_key_exists($col, $this->src))
			? $this->src[$col]
			: (array_key_exists($col, $this->defaults)
				? $this->defaults[$col]
				: null
			); */
		
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
		$this->callEvent('beforeSave');
		if (is_array($this->src))
		{
			// Nový záznam
			$this->callEvent('beforeInsert');
			$insertData = $this->getModifiedDbData(true);
			$tmp = $this->callEvent('insertData', $insertData);
			if ($tmp !== null) $insertData = $tmp;
			$this->src = DBRepository::getDatabase(null, $this->dbInfo)
				->table($this->dbInfo->tableName)
				->insert($insertData);
			$this->converted = [];
			$this->modified = [];
			$this->callEvent('afterInsert');
		}
		else if (get_class($this->src) == 'Nette\\Database\\Table\\ActiveRow')
		{
			$this->callEvent('beforeUpdate');
			$this->src->update($this->getModifiedDbData(false));
			$this->converted = [];
			$this->modified = [];
			$this->callEvent('afterInsert');
		} else {
			throw new \Exception("Can not use Save");
		}
		$this->callEvent('afterSave');
	}

	protected function callEvent($event, ...$params)
	{
		$dbi = $this->dbInfo;
		$r = null;
		if (isset($dbi->events[$event]))
		{
			$method = $dbi->events[$event];
			$r = $this->entity->$method($event, ...$params);
		}

		return $r;
	}
	
	public function toArray($cols = null, $fkObjects = false, $prefix = '')
	{
		$dbi = $this->dbInfo;
		if (!$cols) $cols = array_keys($dbi->columns);
		$res = [];
		foreach($cols as $col)
		{
			$colDef = $dbi->columns[$col];
			if (!$fkObjects && $colDef->fkClass && $col == $colDef->propertyName) continue;
			$res[$prefix.$col] = $this->get($col);
		}
		
		return $res;
	}
}
