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
	 * @param TableInfo[][] $tis
	 */
	public function syncTables(array $tis) : string
	{
		$sqlParts = [];
		foreach($tis as [$tia, $tid])
		{
			if ($tid){
                $this->message("Alter table {$tia->tableName}");
				$this->alterTable($tia, $tid, $sqlParts);
            }
			else {
                $this->message("Create table {$tia->tableName}");
				$this->createTable($tia, $sqlParts);
            }
		}
		$sql = '';
		// dump($sqlParts);
		ksort($sqlParts);
		foreach($sqlParts as $sqlPart)
		{
			$sqlItem = implode(";\n", $sqlPart);
			if ($sqlItem) $sql .= $sqlItem .";\n";
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
		array $allColumns,
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

			$joinLine = implode(' ', $line);
			$sqlParts[self::OrderCol][] = $joinLine;
		}

		// fk
		if ($column->fkClass)
		{
			$fkTi = $column->getFkTableInfo();
			$sqlParts[self::OrderFK][] = 'ALTER TABLE ' . $column->tableInfo->tableName . ' ADD FOREIGN KEY ('.$this->escapeName($column->columnName).') REFERENCES '
				.$this->escapeName($fkTi->tableName)
				.' ('.$this->escapeName(reset($fkTi->primary)->columnName).')';
		}
		if ($column->indexed)
		{
			$sqlParts[self::OrderIndex][] = 'ALTER TABLE ' . $column->tableInfo->tableName . ' ADD INDEX ('.$this->escapeName($column->columnName).')';
		}
	}

	public function getSqlDataType(ColumnInfo $column) : array
	{
		$ch = null;
		$escCN = $this->escapeName($column->columnName);
		switch($column->dbBaseType ?? $column->type)
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

	public function alterTable(TableInfo $appTable, TableInfo $dbTable, array &$sqlAllParts): void
	{

		$sqlAllParts += [
			self::OrderCol => [],
			self::OrderFK => [],
			self::OrderIndex => [],
		];
		// $sqlAllParts[self::OrderCol][] = "-- " . $appTable->tableName;
		// $sqlAllParts[self::OrderFK][] = "-- " . $appTable->tableName;
		// $sqlAllParts[self::OrderIndex][] = "-- " . $appTable->tableName;

		$dbColumnsByName = [];
		foreach ($dbTable->columns as $column) $dbColumnsByName[$column->columnName] = $column;
		$f = true;
		$columns = $appTable->columns;
		// Clear *Id when exists foreign
		foreach ($columns as $k => $column) {
			if ($column->columnName !== $column->propertyName) {
				unset($columns[$column->columnName]);
			}
		}
		foreach ($columns as $appColumn) {
			$dbColumn = $dbColumnsByName[$appColumn->columnName] ?? null;
			$sqlParts = [
				self::OrderCol => [],
				self::OrderFK => [],
				self::OrderIndex => [],
			];
			if ($dbColumn) {
				$this->createColumn($appColumn, $sqlParts, $appTable->columns);
				// print_r($sqlParts);
				if ($dbColumn->nullable != $appColumn->nullable
					|| $dbColumn->dbType != strtolower($this->getSqlDataType($appColumn)[0])
				) {
					foreach($sqlParts[self::OrderCol] as $colSql) {
						$sqlAllParts[self::OrderCol][] = 'ALTER TABLE ' . $appTable->tableName . " MODIFY  $colSql"; // `" . $dbColumn->columnName ."`
					}
					// $f = false;
				}
				$appFkTableInfo = $appColumn->getFkTableInfo();
				if ($dbColumn->fkTable != $appFkTableInfo?->tableName) {
					$sqlAllParts[self::OrderFK] = array_merge(
						$sqlAllParts[self::OrderFK],
						$sqlParts[self::OrderFK]
					);
					if (!$appFkTableInfo) {
						$sqlAllParts[self::OrderFK][] = "-- TODO: Remove foreign key for " . $appColumn->columnName . " to " . $dbColumn->fkTable/* . $nl*/;
					} 
				}
				if (!$dbColumn->indexed && $appColumn->indexed) {
					$sqlAllParts[self::OrderFK] = array_merge(
						$sqlAllParts[self::OrderFK],
						$sqlParts[self::OrderFK]
					);
				}
				if ($dbColumn->indexed && !$appColumn->indexed && !$appColumn->fkClass) {
					$sqlAllParts[self::OrderIndex][] = "-- TODO: Remove index for " . $appColumn->columnName/* . $nl*/;
				}
			} else {
				$this->createColumn($appColumn, $sqlParts, $appTable->columns);
				foreach($sqlParts[self::OrderCol] as $colSql) {
					$sqlAllParts[self::OrderCol][] = 'ALTER TABLE ' . $appTable->tableName . " ADD $colSql";
				}
				$sqlAllParts[self::OrderFK] = array_merge($sqlAllParts[self::OrderFK], $sqlParts[self::OrderFK]);
				$sqlAllParts[self::OrderIndex] = array_merge($sqlAllParts[self::OrderIndex], $sqlParts[self::OrderIndex]);
				/* $sqlAllParts[self::OrderCol][] = 'ALTER TABLE ' . $appTable->tableName . ' ADD ';
				$this->createColumn($appColumn, $sqlAllParts, $appTable->columns);
				$sqlAllParts[self::OrderCol][] = ";$nl"; */
			}
		}
	}

    protected function message(string $message)
    {
        echo "-- $message\n";
    }
}