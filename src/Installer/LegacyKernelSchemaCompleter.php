<?php

/**
 * @copyright Copyright (C) Exponential Platform Contributors. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace App\Installer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Adds to an installed database what the installed Exponential legacy kernel's own schema has
 * and the database lacks.
 *
 * data/sqlite/media_schema.sql and the media demo data describe the legacy kernel of the day
 * they were made. A newer kernel reads tables and columns they do not have, for example
 * expbookmark_folder and ezcontentbrowsebookmark.folder_id (Exponential 6.0.15) on every legacy
 * admin page. The kernel's share/db_schema.dba is the generic schema its own installers use for
 * every database engine. Missing tables are created with their keys and indexes; on existing
 * tables missing columns are added with the indexes over them.
 *
 * One thing that exists is changed: a primary key that is only part of the legacy kernel's key.
 * The legacy kernel keeps a row per version (or status) under the same id, for example
 * ezworkflow (id, version) and ezcontentobject_attribute (id, version). Keyed by the id alone,
 * a new version either fails to insert or, on SQLite with INSERT OR REPLACE, deletes the row of
 * the version before it: an edit in the legacy admin lost the published content. Such a table
 * gets the legacy key, its rows kept (on SQLite Doctrine rebuilds the table and copies them),
 * and on SQLite its serial column loses AUTOINCREMENT, which SQLite allows on a one-column key
 * only (the kernel then numbers new rows itself). Rows lost before the repair cannot be brought
 * back. On MySQL and MariaDB the legacy kernel inserts new rows without the serial column and
 * lets the database number them, so there every serial column of the legacy schema keeps, or
 * gets back, its AUTO_INCREMENT.
 *
 * Run on an existing database by bin/console exponential:legacy:complete-schema.
 */
final class LegacyKernelSchemaCompleter
{
    public function __construct(
        private Connection $db,
        private string $schemaFile,
    ) {
    }

    /**
     * Completes the database with the schema of the project's legacy kernel, then with those of
     * the active legacy extensions that ship one (ezstarrating, ezmbpaex: the legacy admin queried
     * ezx_mbpaex on every sign-in).
     *
     * @return string[] the SQL statements that were executed
     */
    public static function completeProject(Connection $db, string $projectDir): array
    {
        $legacyDir = $projectDir . '/ezpublish_legacy';
        $schemas = [$legacyDir . '/share/db_schema.dba'];
        foreach (self::activeLegacyExtensions($projectDir) as $extension) {
            $file = $legacyDir . '/extension/' . $extension . '/share/db_schema.dba';
            if (\is_file($file)) {
                $schemas[] = $file;
            }
        }

        $queries = [];
        foreach ($schemas as $schemaFile) {
            $queries = array_merge($queries, (new self($db, $schemaFile))->complete());
        }

        return $queries;
    }

    /**
     * The ActiveExtensions[] of the project's global legacy override (as installed, else as shipped).
     *
     * @return string[]
     */
    private static function activeLegacyExtensions(string $projectDir): array
    {
        foreach (['/ezpublish_legacy/settings/override/site.ini.append.php', '/src/install/ezpublish_legacy/settings/override/site.ini.append.php'] as $path) {
            $content = @\file_get_contents($projectDir . $path);
            if (\is_string($content) && \preg_match_all('/^\s*ActiveExtensions\[\]\s*=\s*([A-Za-z0-9_]+)\s*$/m', $content, $m)) {
                return \array_values(\array_unique($m[1]));
            }
        }

        return [];
    }

    /**
     * @return string[] the SQL statements that were executed
     */
    public function complete(): array
    {
        $definition = $this->loadDefinition();
        if ($definition === []) {
            return [];
        }

        $platform = $this->db->getDatabasePlatform();
        // A column declared without a type (SQLite allows it: ezenumvalue.text in the legacy
        // schema) is read as text instead of stopping the introspection.
        if (!$platform->hasDoctrineTypeMappingFor('')) {
            $platform->registerDoctrineTypeMapping('', 'text');
        }
        // Only the tables of the kernel schema are read and compared; the rest of the database
        // (the Ibexa, Netgen Layouts and tags tables) is none of the completer's business.
        $schemaManager = $this->db->getSchemaManager();
        $tables = [];
        foreach (array_keys($definition) as $tableName) {
            if (\is_string($tableName) && $tableName[0] !== '_' && $schemaManager->tablesExist([$tableName])) {
                $tables[] = $schemaManager->listTableDetails($tableName);
            }
        }
        $current = new Schema($tables);
        $target = clone $current;
        foreach ($definition as $tableName => $tableDefinition) {
            if (!\is_string($tableName) || $tableName[0] === '_' || !isset($tableDefinition['fields'])) {
                continue;
            }
            if ($target->hasTable($tableName)) {
                $this->completeTable($target->getTable($tableName), $tableDefinition);
            } else {
                $this->createTable($target, $tableName, $tableDefinition);
            }
        }

        $queries = (new Comparator())->compare($current, $target)->toSaveSql($this->db->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->db->executeStatement($query);
        }

        if (!$platform instanceof SqlitePlatform) {
            $queries = array_merge($queries, $this->restoreSerialColumns($definition));
        }

        return $queries;
    }

    /**
     * Gives every serial column of the legacy schema AUTO_INCREMENT where the database lacks it
     * (not on SQLite). Changing a primary key over a serial column makes Doctrine drop the column's
     * AUTO_INCREMENT before it drops the key, and a database completed by an earlier version of this
     * class lost it on the composite keys; without it the legacy kernel's inserts all get the id 0,
     * so the second new object of a class fails with "Duplicate entry '0-1' for key 'PRIMARY'".
     *
     * @return string[] the SQL statements that were executed
     */
    private function restoreSerialColumns(array $definition): array
    {
        $schemaManager = $this->db->getSchemaManager();
        $tables = [];
        foreach ($definition as $tableName => $tableDefinition) {
            if (!\is_string($tableName) || $tableName[0] === '_' || !isset($tableDefinition['fields'])) {
                continue;
            }
            foreach ($tableDefinition['fields'] as $fieldName => $field) {
                if (($field['type'] ?? '') === 'auto_increment' && $schemaManager->tablesExist([$tableName])) {
                    $tables[$tableName][] = (string) $fieldName;
                }
            }
        }
        $current = [];
        foreach (array_keys($tables) as $tableName) {
            $current[] = $schemaManager->listTableDetails($tableName);
        }
        $current = new Schema($current);
        $target = clone $current;
        foreach ($tables as $tableName => $columns) {
            $table = $target->getTable($tableName);
            foreach ($columns as $column) {
                if ($table->hasColumn($column) && !$table->getColumn($column)->getAutoincrement()) {
                    // an AUTO_INCREMENT column takes no default (MySQL: "Invalid default value")
                    $table->getColumn($column)->setAutoincrement(true)->setNotnull(true)->setDefault(null);
                }
            }
        }
        $queries = (new Comparator())->compare($current, $target)->toSaveSql($this->db->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->db->executeStatement($query);
        }

        return $queries;
    }

    private function loadDefinition(): array
    {
        if (!is_readable($this->schemaFile)) {
            return [];
        }
        $schema = null;
        include $this->schemaFile;

        return \is_array($schema) ? $schema : [];
    }

    private function createTable(Schema $schema, string $tableName, array $tableDefinition): void
    {
        $table = $schema->createTable($tableName);
        foreach ($tableDefinition['fields'] as $fieldName => $field) {
            $this->addColumn($table, (string) $fieldName, $field);
        }
        foreach ($tableDefinition['indexes'] ?? [] as $indexName => $index) {
            $this->addIndex($table, (string) $indexName, $index);
        }
        $this->keepSerialColumnsOutOfCompositeKey($table);
    }

    private function completeTable(Table $table, array $tableDefinition): void
    {
        $this->completePrimaryKey($table, $tableDefinition);

        $added = [];
        foreach ($tableDefinition['fields'] as $fieldName => $field) {
            if (!$table->hasColumn((string) $fieldName)) {
                $this->addColumn($table, (string) $fieldName, $field);
                $added[] = (string) $fieldName;
            }
        }
        if ($added === [] || empty($tableDefinition['indexes'])) {
            return;
        }
        foreach ($tableDefinition['indexes'] as $indexName => $index) {
            $fields = $this->indexFields($index);
            if ($index['type'] !== 'primary' && !$table->hasIndex((string) $indexName) && array_intersect($fields, $added) !== []) {
                $this->addIndex($table, (string) $indexName, $index);
            }
        }
    }

    /**
     * Gives the table the legacy primary key when its own key is a strict part of it (or missing),
     * for example (id) where the legacy kernel keys the table by (id, version).
     */
    private function completePrimaryKey(Table $table, array $tableDefinition): void
    {
        $legacy = [];
        foreach ($tableDefinition['indexes'] ?? [] as $index) {
            if (($index['type'] ?? '') === 'primary') {
                $legacy = $this->indexFields($index);
            }
        }
        if ($legacy === [] || array_diff($legacy, array_map('strval', array_keys($table->getColumns()))) !== []) {
            return;
        }
        $legacyLower = array_map('strtolower', $legacy);
        $current = $table->getPrimaryKey() !== null ? array_map('strtolower', $table->getPrimaryKey()->getColumns()) : [];
        if (\count($current) >= \count($legacyLower) || array_diff($current, $legacyLower) !== []) {
            return;
        }

        $table->dropPrimaryKey();
        $table->setPrimaryKey($legacy);
        $this->keepSerialColumnsOutOfCompositeKey($table);
    }

    /**
     * SQLite numbers only a single INTEGER PRIMARY KEY; Doctrine turns an AUTOINCREMENT column into
     * that key and drops the rest of a composite key. A serial column of a composite key is a plain
     * NOT NULL integer: the legacy kernel and the Platform give it the next value themselves.
     * Other engines keep AUTO_INCREMENT on the first column of a composite key, and the legacy
     * kernel relies on it there (it inserts new attribute rows without an id).
     */
    private function keepSerialColumnsOutOfCompositeKey(Table $table): void
    {
        if (!$this->db->getDatabasePlatform() instanceof SqlitePlatform) {
            return;
        }
        $primary = $table->getPrimaryKey();
        if ($primary === null || \count($primary->getColumns()) < 2) {
            return;
        }
        foreach ($primary->getColumns() as $column) {
            $table->getColumn($column)->setAutoincrement(false)->setNotnull(true);
        }
    }

    private function addColumn(Table $table, string $name, array $field): void
    {
        $length = $field['length'] ?? null;
        $options = ['notnull' => !empty($field['not_null'])];
        switch ($field['type']) {
            case 'auto_increment':
                $type = 'integer';
                $options['notnull'] = true;
                $options['autoincrement'] = true;
                break;
            case 'int':
                $type = 'integer';
                break;
            case 'bigint':
                $type = 'bigint';
                break;
            case 'varchar':
            case 'char':
                $type = 'string';
                $options['length'] = $length ?: 255;
                $options['fixed'] = $field['type'] === 'char';
                break;
            case 'float':
            case 'double':
                $type = 'float';
                break;
            case 'decimal':
                $type = 'decimal';
                [$precision, $scale] = array_pad(explode(',', (string) $length), 2, 0);
                $options['precision'] = (int) $precision ?: 10;
                $options['scale'] = (int) $scale;
                break;
            default:
                // longtext, mediumtext, text: MySQL gives these columns no default
                $type = 'text';
                $field['default'] = null;
        }
        if (isset($field['default']) && $field['default'] !== false) {
            $options['default'] = $field['default'];
        }
        $table->addColumn($name, $type, $options);
    }

    private function addIndex(Table $table, string $name, array $index): void
    {
        $fields = $this->indexFields($index);
        switch ($index['type']) {
            case 'primary':
                $table->setPrimaryKey($fields);
                break;
            case 'unique':
                $table->addUniqueIndex($fields, $name);
                break;
            default:
                $table->addIndex($fields, $name);
        }
    }

    /**
     * @return string[]
     */
    private function indexFields(array $index): array
    {
        $fields = [];
        foreach ($index['fields'] as $field) {
            $fields[] = \is_array($field) ? $field['name'] : $field;
        }

        return $fields;
    }
}
