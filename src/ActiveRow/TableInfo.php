<?php

namespace Murdej\ActiveRow;

use BCNette\Reflection\ClassType;

class TableInfo
{
	public $tableName;
	
	public $className;

	/** @var ColumnInfo[] */
	public $primary = [];
	
	public $fkColumns = [];
	
	/** @var ColumnInfo[] */
	public $columns = [];
	
	public $defaults = [];
	
	public $relateds = [];
	
	public $defaultOrder = null;

	public $events = [];
	
	public function parseClass($cn)
	{
		$ref = new ClassType($cn);
		$anns = $ref->getAnnotations();
		// dump($anns);
		$this->className = $cn;
		list($ns, $scn) = self::splitClassName($cn);
		if (!isset($anns['dbTable']) && !isset($anns['property'])) return null;
		if (isset($anns['dbTable']) && $anns['dbTable'][0]) 
		{
			if (is_string($anns['dbTable'][0]))
			{
				$this->tableName = $anns['dbTable'][0];
			} 
			else if ($anns['dbTable'][0] == true) 
			{
				$this->tableName = Convention::deriveTableNameFromClass($ns, $scn);
			}
			else throw new \Exception("Invalid table def $anns[dbTable][0]");
		}
		// Výchzí hodnoty
		if (isset($anns['defaultValues']))
		{
			//todo: Další možnosti - jiný název metody, statická property
			$this->defaults = $cn::defaultValues();
		}
		
		if (!isset($anns['property'])) throw new \Exception("Must define any property");
		foreach($anns['property'] as $pa)
		{
			$ci = new ColumnInfo($pa, $ns, $this);
			// $ci->tableInfo = $this;
			$this->columns[$ci->propertyName] = $ci;
			if ($ci->fkClass)
			{
				$this->columns[$ci->columnName] = $ci;
			}
			if ($ci->primary) $this->primary[$ci->propertyName] = $ci;
			if ($ci->defaultValue) $this->defaults[$ci->propertyName] = $ci->defaultValue;
		}
		if (isset($anns['related'])) foreach($anns['related'] as $pa)
		{
			$ri = new RelatedInfo($pa, $ns);
			//todo: Kontrola na duplicitní název se sloupcem
			$this->relateds[$ri->propertyName] = $ri;
		}
		if (isset($anns['defaultOrder'])) 
		{
			$this->defaultOrder = $anns['defaultOrder'][0];
		}

		if (isset($anns['event']))
		{
			foreach($anns['event'] as $ev)
			{
				$tmp = explode(' ', $ev);
				if (count($tmp) == 2)
					$this->events[$tmp[0]] = $tmp[1];
				else
					$this->events[$tmp[0]] = $tmp[0];
			}
		}	
	}

	public function existsCol($col) 
	{
		return isset($this->columns[$col]) || isset($this->fkColumns[$col]);
	}
	
	public function existsRelated($col)
	{
		return isset($this->relateds[$col]);
	}
	
	public function __construct($cn)
	{
		if ($cn) $this->parseClass($cn);
	}

	protected static $dbInfoCache = [];
	
	public static function get($className) : TableInfo
	{
		if (!isset(self::$dbInfoCache[$className]))
		{
			self::$dbInfoCache[$className] = new TableInfo($className);
		}
		
		return self::$dbInfoCache[$className];
	}

    public static function tableName($className) : string
    {
        self::get($className)->tableName;
    }

    public static function getFullClassName($className, $nameSpace)
	{
		return (strpos($className, '\\') == false) 
			? $nameSpace.'\\'.$className
			: $className;
	}
	
	public static function splitClassName($className)
	{
		$p = strrpos($className, '\\');
		return ($p === false)
			? ['', $className]
			: [substr($className, 0, $p), substr($className, $p + 1)];
	}

	protected $_columnNames = null;

	public function getColumnNames(?bool $inDb = null)
	{
		if ($this->_columnNames === null)
		{
			$this->_columnNames = [];
			foreach($this->columns as $colName => $col)
			{
				// dump($col);
                if ($inDb === null || ($inDb === ($col->columnName === $col->propertyName))) {
                    $this->_columnNames[] = $colName; //$col->propertyName;
                    // if ($col->fkClass) $this->_columnNames[] = $col->propertyName;
                }
			}
		}

		return $this->_columnNames;
	}
}
