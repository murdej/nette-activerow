<?php declare(strict_types=1);

namespace Murdej\ActiveRow\Tools;

use Murdej\ActiveRow\ColumnInfo;
use Murdej\ActiveRow\TableInfo;
use Nette\Database\Explorer;
use Nette\Database\Context;

class DbInfoLoader
{

    public function __construct(
         /** @property Explorer|Context $database */
        protected Explorer|Context $database,
    )
    {

    }

    public function getTableInfo(string $tableName): ?TableInfo
    {
        if (!$this->existsTable($tableName)) return null;
        $columns = [];
        $tableInfo = new TableInfo(null);
        foreach ($this->database->query('DESCRIBE ' . $tableName)->fetchAll() as $dbColumn) {
            $column = new ColumnInfo(null, null, $tableInfo);
            $column->columnName = $dbColumn['Field'];
            $column->defaultValue = $dbColumn->Default;
            $column->dbType = $dbColumn['Type'];
            $column->autoIncrement = $dbColumn['Extra'] === 'auto_increment';
            $column->nullable = $dbColumn['Null'] == 'YES';
            $columns[$dbColumn['Field']] = $column;
            $tableInfo->columns[] = $column;
        }

        foreach ($this->database->query('SELECT *
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME IS NOT NULL
                AND TABLE_NAME = ?
                AND TABLE_SCHEMA = DATABASE()',
            $tableName
        ) as $foreignKey) {
            $columns[$foreignKey->COLUMN_NAME]->fkTable = $foreignKey->REFERENCED_TABLE_NAME;
        }

        foreach ($this->database->query('SHOW INDEX FROM ' . $tableName) as $row) {
            $columns[$row['Column_name']]->indexed = $row->Non_unique;

        }

        return $tableInfo;
    }

    public function existsTable(string $tableName): bool
    {
        return !!$this->database->query(
            'SELECT * FROM `information_schema`.`TABLES`
            WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()',
            $tableName,
        )->fetch();
    }
}