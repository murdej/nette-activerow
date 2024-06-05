<?php

namespace  Murdej\ActiveRow;

class BaseEntity implements \JsonSerializable
{
	use TBaseEntity;

	use TStaticRepository;

}

trait TBaseEntity
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

	public function __isset($key)
	{
		return $this->_dbEntity->isset($key);
	}

	public function __construct($src = [], $db = null)
	{
		$this->_dbEntity = new DBEntity($this, $src, $db);
		$this->__init();
	}

	public function __init()
	{
	}

	public function save() 
	{
		return $this->_dbEntity->save();
	}

	public function toArray($cols = null, $fkObjects = false, $prefix = '')
	{
		return $this->_dbEntity->toArray($cols, $fkObjects, $prefix);
	}

	/**
	 * @param []|\ArrayAccess $values
	 * @param []|null $cols
	 * @param []|null $ignoreCols
	 */
	public function fromArray($values, $cols = null, $ignoreCols = ['id'])
	{
		$columnNames = $this->_dbEntity->getDbInfo()->getColumnNames();
		// dump($columnNames);
		foreach ($values as $key => $value)
		{
			if (in_array($key, $columnNames)
				&& ($cols === null || in_array($key, $cols))
				&& ($ignoreCols === null || !in_array($key, $ignoreCols))
			) $this->$key = $value;
		}
	}

	// Backward compatibility
	#[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}

trait TStaticRepository
{
	/** @return ?static */
	public static function get($pk, $db = null)
	{
		return self::repository($db)->get($pk, $db);
	}

	/** @return static */
	public static function createNew($data, $db = null)
	{
		$ent = new static();
		$ent->fromArray($data);
		return $ent;
	}

	/** @return ?static */
	public static function getBy($filter, $db = null)
	{
		return self::repository($db)->getBy($filter);
	}

	/** @return static[]|DBSelect */
	public static function findBy($filter, $db = null)
	{
		return self::repository($db)->newSelect()->where($filter);
	}

	/** @return static[]|DBSelect */
	public static function findAll($db = null)
	{
		return self::repository($db)->newSelect();
	}

	/**
	@return libs\ActiveRow\DBRepository
	 **/
	public static function repository($db = null) : DBRepository
	{
		return new DBRepository(get_called_class(), $db);
	}
}
