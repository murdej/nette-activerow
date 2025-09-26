<?php declare(strict_types=1);

namespace Murdej\ActiveRow;

class DBModifyMonitor
{
    public static function saveRecord(string $table, string $operation, array $data, ?int $insertId): void
    {
        file_put_contents(
            '/tmp/db-log',
            json_encode([
                time(), $table, $operation, $data, $insertId
            ]) . "\n",
            FILE_APPEND,
        );
    }
}