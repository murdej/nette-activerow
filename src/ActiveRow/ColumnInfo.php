<?php

namespace  Murdej\ActiveRow;

use Nette\SmartObject;

/**
 * @property string $fullName
 * @property string $propertyInfo
 */
class ColumnInfo // extends \Nette\Object
{
	use SmartObject;

	public $columnName;
	
	public $propertyName;
	
	public $type;
	
	public $typeLen = null;
	
	public $typeDec = null;
	
	public $defaultValue = null;
	
	public $unique = false;
	
	public $primary = false;
	
	public $indexed = false;
	
	public $forInsert = true;
	
	public $nullable = false;

	public $blankNull = false;
	
	public $forUpdate = true;
	
	public $fkClass = null;
	
	public $fkTable = null;
	
	public $autoIncrement = null;

	public $serialize = false;
	
	public $tableInfo = null;
	
	public function getFullName()
	{
		return $this->propertyName;
	}
	
	// typ[velikost,dec,default](flag,...,!flag) nazev
	public function parseAnnotation($ann, $ns)
	{
		// dump($ann);
		if (is_string($ann)) {
			$m1 = null;
			$m2 = null;
			if (
				preg_match('/^([\\\\?A-Za-z_][\\\\0-9A-Za-z_]*)(\\[([0-9]*)(,[0-9]*)?(,[^\\]]*)?\\])? *(\\(([!\\?A-Za-z_0-9,]*)\\))? \\$([A-Za-z_][0-9A-Za-z_]*)$/', $ann, $m1)
				|| preg_match('/^([\\\\?A-Za-z_][\\\\0-9A-Za-z_]*) \\$([A-Za-z_][0-9A-Za-z_]*) *(\\[([0-9]*)(,[0-9]*)?(,[^\\]]*)?\\])? *(\\(([!\\?A-Za-z_0-9,]*)\\))?$/', $ann, $m2)
				)
			{
				if ($m1)
				{
					//0, 1,     2,  3,        4,        5,             6,  7       8
					[$_, $type, $_, $typeLen, $typeDec, $defaultValue, $_, $flags, $propertyName] = $m1 + [null, null, null, null, null, null, null, null, null];
				} 
				else if ($m2) 
				{
					//0, 1,     2,             3,  4,        5,        6,             7   8
					[$_, $type, $propertyName, $_, $typeLen, $typeDec, $defaultValue, $_, $flags] = $m2 + [null, null, null, null, null, null, null, null, null];
				}
				// dump($m);
				$flagList = explode(',', $flags);
				if (in_array("autoIncrement", $flagList)) $type = "autoIncrement";
				if (in_array("json", $flagList)) $type = "json";
				$this->propertyName = $this->columnName = trim($propertyName);
				$this->type = trim($type);
				if ($this->type[0] == '?')
				{
					$this->type = substr($this->type, 1);
					$this->nullable = true;
				}
				if ($this->type == "\DateTime") $this->type == "DateTime";
				if ($this->type == 'autoIncrement')
				{
					Convention::autoIncrement($this);
				}
				$this->typeLen = trim($typeLen) ? (int)$typeLen : null;
				$this->typeDec = strlen(trim($typeDec)) > 1 ? (int)substr($typeDec, 1) : null;
				$this->defaultValue = strlen(trim($defaultValue)) > 1 ? substr($defaultValue, 1) : null;
				if ($this->defaultValue)
				{
					switch($this->type)
					{
						case 'json':
							switch($this->defaultValue)
							{
								case 'n':
									$this->defaultValue = null;
									break;
								case 'l':
								case 'a':
								case 'd':
									$this->defaultValue = [];
									break;
								case 't':
									$this->defaultValue = true;
									break;
								case 'f':
									$this->defaultValue = false;
									break;
								default:
									$this->defaultValue = json_decode($this->defaultValue, true);
									break;
							}
							break;
					}
				}
				$flagAlias = [ '?' => 'nullable', 'pk' => 'primary', "index" => "indexed" ];
				foreach($flagList as $flag)
				{
					$flag = trim($flag);
					if (isset($flagAlias[$flag])) $flag = $flagAlias[$flag];
					if ($flag) 
					{
						$flagValue = $flag[0] != '!';
						if (!$flagValue) $flag = substr($flag, 1);
						switch($flag)
						{
							case 'unique':
							case 'primary':
							case 'indexed':
							case 'forInsert':
							case 'forUpdate':
							case 'serialize':
							case 'nullable':
							case 'autoIncrement':
							case 'blankNull':
								$this->$flag = $flagValue;
								break;
							case 'fk':
								$this->fkClass = TableInfo::getFullClassName($this->type, $ns);
								//todo: detekovat podle PK druhé tabulky
								$this->type = 'int';
								$this->columnName = $this->propertyName.'Id';
								break;
							case "autoincrement": // pseudotypes
							case "json":
								break;
							default:
								throw new \Exception("Invalid column flag '$flag', property '$this->propertyInfo'");
								break;
						}
					}
				}
			} else throw new \Exception("Invalid column def '$ann', property '$this->propertyInfo'");
		} else {
			foreach($ann as $k => $v) 
			{
				if ($k == 'name')
				{
					$this->propertyName = $v;
					$this->columnName = $v;
				} else $this->$k = $v;
			}
		}
	}

	public function getPropertyInfo()
	{
		return $this->tableInfo->className."::".$this->propertyName;
	}
	
	public function __construct($ann, $ns, $tableInfo)
	{
		$this->tableInfo = $tableInfo;
		$this->parseAnnotation($ann, $ns);
	}
	
	public static $config = [
		'namingConvence' => [
			'fkSuffix' => 'Id',
		]
	];
	
}