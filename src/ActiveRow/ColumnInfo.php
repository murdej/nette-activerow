<?php

namespace  Murdej\ActiveRow;

class ColumnInfo extends \Nette\Object
{
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
			if (preg_match('/^([\\\\A-Za-z_][\\\\0-9A-Za-z_]*)(\\[([0-9]*)(,[0-9]*)?(,[^\\]]*)?\\])? *(\\(([!\\?A-Za-z_0-9,]*)\\))? \\$([A-Za-z_][0-9A-Za-z_]*)$/', $ann, $m))
			{
				// dump($m);
				$this->propertyName = $this->columnName = trim($m[8]);
				$this->type = trim($m[1]);
				if ($this->type == 'autoIncrement')
				{
					Convention::autoIncrement($this);
				}
				$this->typeLen = trim($m[3]) ? (int)$m[3] : null;
				$this->typeDec = strlen(trim($m[4])) > 1 ? (int)substr($m[4], 1) : null;
				$this->defaultValue = strlen(trim($m[5])) > 1 ? substr($m[5], 1) : null;
				$flagAlias = [ '?' => 'nullable' ];
				foreach(explode(',', $m[7]) as $flag)
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
							default:
								throw new \Exception("Invalid column flag '$flag'");
								break;
						}
					}
				}
			} else throw new \Exception("Invalid column def '$ann'");
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
	
	public function __construct($ann, $ns)
	{
		$this->parseAnnotation($ann, $ns);
	}
	
	public static $config = [
		'namingConvence' => [
			'fkSuffix' => 'Id',
		]
	];
	
}