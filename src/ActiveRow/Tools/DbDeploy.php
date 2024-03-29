<?php

namespace Murdej\ActiveRow\Tools;

use Murdej\ActiveRow\TableInfo;
use Murdej\ActiveRow\ColumnInfo;

class DbDeploy
{
	const OrderCol = 10;

	const OrderTable = 10;

	const OrderIndex = 20;

	const OrderFK = 30;

	public function createTable(TableInfo $ti, array &$sqlParts) 
	{
		$nl = "\n";
		$tableSqlParts = [
			self::OrderCol => [],
			self::OrderIndex => [],
			self::OrderFK => [],
		];
        $columnsByPropertyName = []; // $ti->columns;
        foreach ($ti->columns as $column) $columnsByPropertyName[$column->propertyName] = $column;
        foreach ($columnsByPropertyName as $k => $column) {
            if ($column->fkClass && isset($columnsByPropertyName[$column->propertyName]))
                $columnsByPropertyName[$column->columnName] = $column;
        }
		foreach(array_unique(array_map(fn($c) => $c->columnName, $columnsByPropertyName)) as $k) // $ti->columns as $k => $column)
        // foreach($columnsByPropertyName as $column)
		{
            $column = $columnsByPropertyName[$k];
			// if ($k == $column->columnName)
            $this->createColumn($column, $tableSqlParts, $ti->columns);
		}
		if (!$ti->tableName) throw new \Exception("No tableName for ".$ti->className);
		$sql = 'CREATE TABLE '.$this->escapeName($ti->tableName)." ($nl";
		$sql .= implode(",$nl", $tableSqlParts[self::OrderCol]);
		$sql .= "$nl)";

		$sqlParts[self::OrderTable][] = $sql;

		foreach([self::OrderIndex, self::OrderFK] as $i)
		{
			foreach($tableSqlParts[$i] as $item)
			{
				$sqlParts[$i][] = "ALTER TABLE ".$this->escapeName($ti->tableName)." ADD ".$item;
			}
		}
	}

	/**
	 * @param TableInfo[] $tis
	 */
	public function createTables(array $tis) : string
	{
		$sqlParts = [];
		foreach($tis as $ti)
		{
			$this->createTable($ti, $sqlParts);
		}
		$sql = '';
		// dump($sqlParts);
		ksort($sqlParts);
		foreach($sqlParts as $sqlPart)
		{
			$sql .= implode(";\n", $sqlPart).";\n";
		}

		return $sql;
	}

    /**
     * @param ColumnInfo $column
     * @param array $sqlParts
     * @param ColumnInfo[] $allColumns
     * @return void
     * @throws \Exception
     */
	public function createColumn(
        ColumnInfo $column,
        array &$sqlParts,
        array $allColumns
    ) {
        // dump($allColumns);
		$line = [];
        // $eq = $column->columnName == $column->propertyName;
        $identical = true; // $eq; // || (($column->fkClass || $column->fkTable) && $column->type !== "int");

        /* if (!$identical) {
            // projdi všechny pole a pokud neexistuje *Id tak pusť
            $existsId = false;
            foreach ($allColumns as $c) {
                if ($column->columnName == $c->columnName && ($c->fkClass !! $c->propertyName)) {
                    $existsId = true;
                    break;
                }
            }
            $identical = !$existsId;
        } */
            // $column->columnName == $column->propertyName
            // || !count(array_filter($allColumns, fn($c) => $c->propertyName == $c->columnName && $c->propertyName == $column->propertyName))

        if ($identical)
        {
            $line[] = $this->escapeName($column->columnName);
            $t = $this->getSqlDataType($column);
            $line[] = $t[0];
            $line[] = $column->nullable ? 'NULL' : 'NOT NULL';
            if ($column->autoIncrement)
            {
                $line[] = 'AUTO_INCREMENT PRIMARY KEY';
            }
            if ($column->unique) $line[] = 'UNIQUE KEY';
            if ($column->primary) $line[] = 'PRIMARY KEY';
            if (isset($t[1])) $line[] = $t[1];

		    $sqlParts[self::OrderCol][] = implode(' ', $line);
        }

		// fk
		if ($column->fkClass)
		{
			$fkTi = TableInfo::get($column->fkClass);
			$sqlParts[self::OrderFK][] = 'FOREIGN KEY ('.$this->escapeName($column->columnName).') REFERENCES '
				.$this->escapeName($fkTi->tableName)
				.' ('.$this->escapeName(reset($fkTi->primary)->columnName).')';
		}
		if ($column->indexed)
		{
			$sqlParts[self::OrderIndex][] = 'INDEX ('.$this->escapeName($column->columnName).')';
		}
	}

	public function getSqlDataType(ColumnInfo $column) : array
	{
		$ch = null;
		$escCN = $this->escapeName($column->columnName);
		switch($column->type)
		{
			case 'int':
				$t = 'INT('.($column->typeLen ? $column->typeLen : 10).')';
				break;
			case 'decimal':
			case 'double':
			case 'float':
				$l = $column->typeLen ? $column->typeLen : 10;
				$d = $column->typeDec ? $column->typeLen : 3;
				$t = strtoupper($column->type).'('.($l + $d).','.$d.')';
				break;
			case 'json':
			case 'string':
				$t = $column->typeLen
					? 'VARCHAR('.$column->typeLen.')'
					: 'TEXT';
				if ($column->type == 'json') $ch = "CHECK($escCN IS NULL OR JSON_VALID($escCN))";
				break;
			case 'bool':
				$t = 'TINYINT(1)';
				$ch = "CHECK($escCN IN (1, 0))";
				break;
			case 'DateTime':
			case '\\DateTime':
				$t = 'DATETIME';
				break;
			default:
				throw new \Exception("Unknown type ".($column->tableInfo ? $column->tableInfo->className.'::' : '')."$column->type");
		}
		return [$t, $ch];
	}

	public function escapeName(string $name) : string
	{
		return "`$name`";
	}
}