<?php

namespace  Murdej\DataMapper;

class Converter
{
	public static function get()
	{
		return new Converter();
	}
	
	// Konverze z DB do Ent
	public function convertTo($value, $columnInfo, $col)
	{
		if ($columnInfo->serialize)
		{
			$className = $columnInfo->type;
			$obj = new $className();
			$obj->fromDbValue($value);
			
			return $obj;
		}
		if ($value === null)
		{
			if ($columnInfo->nullable) return null;
			else throw new \Exception("Column $columnInfo->fullName is not nullable");
		}
		if ($columnInfo->fkClass && $col == $columnInfo->propertyName) 
		{
			$cls = $columnInfo->fkClass;
			return $cls::repository()->createEntity($value);
		} 
		else 
		{
			switch($columnInfo->type)
			{
				case 'int':
					return (int)$value;
				case 'double':
					return (double)$value;
				case 'json':
					return json_decode($value);
				case 'string':
					return (string)$value;
				case 'bool':
					switch(true)
					{
						case $value === 0:
						case $value === '':
						case $value === 'false':
						case $value === 'no':
						case $value === 'off':
						case $value === "\x00":
						case $value === false;
							return false;
						case $value === 1:
						case $value === 'true':
						case $value === 'yes':
						case $value === 'on':
						case $value === "\x01":
						case $value === true:
							return true;
						default:
							throw new \Exception("Invalid bool value $value");
					}
				case 'DateTime':
					$dt = new \DateTime();
					$dt->setTimestamp((int)$value);
					return $dt;
				default:
					throw new \Exception("Unknown type ".($columnInfo->tableInfo ? $columnInfo->tableInfo->className.'::' : '')."$columnInfo->type");
					
			}
		}
	}

	public function convertFrom($value, $columnInfo)
	{
		if ($value === null) return null;
		if ($columnInfo->serialize)
		{
			return $value->toDbValue();
		} 
		else
		{
			switch($columnInfo->type)
			{
				case 'json':
					return json_encode($value);
				case 'DateTime':
					return $value->getTimestamp();
			}
			return $value;
		}
	}
}