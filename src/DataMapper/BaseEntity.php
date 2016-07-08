<?php

namespace  Murdej\DataMapper;

class BaseEntity 
{
	public $_dbEntity;
	
	public function __get($key)
	{
		return $this->_dbEntity->get($key);
	}
	
	public function __set($key, $value)
	{
		$this->_dbEntity->set($key, $value);
	}
	
	public function __construct($src = [], $db = null)
	{
		$this->_dbEntity = new DBEntity($this, $src, $db);
	}
	
	public function save()
	{
		$this->_dbEntity->save();
	}
	
	public function toArray($cols = null, $fkObjects = false)
	{
		return $this->_dbEntity->toArray($cols, $fkObjects);
	}
	
	public static function get($pk, $db = null)
	{
	return self::repository($db)->get($pk, $db);
	}
	
	public static function getBy($filter, $db = null)
	{
		return self::repository($db)->getBy($filter, $db);
	}
	
	public static function findBy($filter, $db = null)
	{
		return self::repository($db)->newSelect()->where($filter, $db);
	}
	
	public static function repository($db = null)
	{
		return new DBRepository(get_called_class(), $db);
	}
}
