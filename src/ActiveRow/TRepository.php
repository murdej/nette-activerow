<?php

namespace Murdej\ActiveRow;

/**
 * @template T
 */
trait TRepository
{
    /** @var DBRepository  */
	public /*DBRepository*/ $dbRepository;

	/** @return ?T */
	public function get($pk)
	{
		return $this->dbRepository->get($pk);
	}

	/** @return T */
	public function createNew($data = [])
	{
		$cn = $this->dbRepository->className;
		$ent = new $cn();
		$ent->_dbEntity->db = $this->dbRepository->getDb();

		$ent->fromArray($data);
		return $ent;
	}

	/** @return ?T */
	public function getBy($filter)
	{
		return $this->dbRepository->getBy($filter);
	}

	/** @return T[]|DBSelect */
	public function findBy($filter)
	{
		return $this->dbRepository->newSelect()->where($filter);
	}

	/** @return T[]|DBSelect */
	public function findAll()
	{
		return $this->dbRepository->newSelect();
	}
}
