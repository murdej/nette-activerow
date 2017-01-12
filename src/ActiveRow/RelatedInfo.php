<?php

namespace  Murdej\ActiveRow;

use \Murdej\ActiveRow\TableInfo;

class RelatedInfo extends \Nette\Object
{
	public $relClass = null;
	
	public $relColumn = null;
	
	public $propertyName = null;
	
	protected $_tableName = null;
	
	public function parseAnnotation($ann, $ns)
	{
		// dump($ann);
		if (is_string($ann)) {
			if (preg_match('/^([A-Za-z_][\\\\0-9A-Za-z_]*) \\$([A-Za-z_][0-9A-Za-z_]*)( +[A-Za-z_][0-9A-Za-z_]*)?$/', $ann, $m))
			{
				$this->relClass = TableInfo::getFullClassName($m[1], $ns);
				$this->propertyName = trim($m[2]);
				$this->relColumn = isset($m[3]) ? trim($m[3]) : null;
			} else throw new \Exception("Invalid related def '$ann'");
		} else {
			foreach($ann as $k => $v) 
			{
				$this->$k = $v;
			}
		}
	}
	
	public function getRelTableName()
	{
		if (!$this->_tableName)
		{
			$this->_tableName = TableInfo::get($this->relClass)->tableName;
		}
		return $this->_tableName;
	}

	public function __construct($ann, $ns)
	{
		$this->parseAnnotation($ann, $ns);
	}
}
