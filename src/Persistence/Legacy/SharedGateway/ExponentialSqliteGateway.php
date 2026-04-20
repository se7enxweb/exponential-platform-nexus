<?php

declare(strict_types=1);

namespace App\Persistence\Legacy\SharedGateway;

use Doctrine\DBAL\Connection;
use eZ\Publish\Core\Persistence\Legacy\SharedGateway\Gateway;

/**
 * SQLite-specific shared-gateway override for Exponential Platform v3.
 *
 * The vendor SqliteGateway uses hrtime(true) to generate next integer IDs.
 * That nanosecond timestamp overflows SQLite's INTEGER range and collides with
 * already-imported seed data (e.g. id=4708).
 *
 * This implementation issues SELECT COALESCE(MAX(col), 0) + 1 FROM table,
 * which is safe for single-threaded SQLite dev environments and always
 * produces an ID above every existing row.
 *
 * @internal
 */
final class ExponentialSqliteGateway implements Gateway
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /** @var array<string, int> */
    private $lastInsertedIds = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getColumnNextIntegerValue(
        string $tableName,
        string $columnName,
        string $sequenceName
    ): ?int {
        $result = $this->connection
            ->executeQuery(
                sprintf('SELECT COALESCE(MAX(%s), 0) + 1 FROM %s', $columnName, $tableName)
            )
            ->fetchOne();

        return $this->lastInsertedIds[$sequenceName] = (int) $result;
    }

    public function getLastInsertedId(string $sequenceName): int
    {
        if (!isset($this->lastInsertedIds[$sequenceName])) {
            return (int) $this->connection->lastInsertId($sequenceName);
        }

        return $this->lastInsertedIds[$sequenceName];
    }
}
