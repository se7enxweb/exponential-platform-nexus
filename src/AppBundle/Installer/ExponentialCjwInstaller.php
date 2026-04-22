<?php

declare(strict_types=1);

namespace AppBundle\Installer;

use Doctrine\DBAL\Connection;
use EzSystems\DoctrineSchema\API\Builder\SchemaBuilder;
use EzSystems\PlatformInstallerBundle\Installer\CoreInstaller;

/**
 * Provides the `exponential-cjw` install type.
 *
 * Extends the standard CoreInstaller with CJW-specific extras:
 *
 *  importSchema():
 *    On pdo_sqlite: creates ALL tables (eZ Platform + cjwnl_*) from
 *       src/AppBundle/Resources/database/sql/sqlite/schema.sql
 *       (auto-generated from schema/schema.sql by mysql_to_sqlite_cjw.py).
 *    This preserves composite primary keys (e.g. id+version on
 *    ezcontentobject_attribute) matching MySQL structure, allowing all
 *    content versions to be imported without UNIQUE constraint violations.
 *    On MySQL/MariaDB: falls back to the standard SchemaBuilder DDL.
 *
 *  importData():
 *    On pdo_sqlite: seeds all tables from
 *       src/AppBundle/Resources/database/sql/sqlite/data.sql
 *       (auto-generated from data/content.sql by mysql_to_sqlite_cjw.py).
 *    On MySQL/MariaDB: falls back to the standard CoreInstaller cleandata.sql.
 *
 * SQL files are located relative to %kernel.project_dir%:
 *   src/AppBundle/Resources/database/sql/sqlite/schema.sql
 *   src/AppBundle/Resources/database/sql/sqlite/data.sql
 */
class ExponentialCjwInstaller extends CoreInstaller
{
    private const CJW_SQL_DIR = '/src/AppBundle/Resources/database/sql/sqlite/';

    /** @var string */
    private $projectDir;

    public function __construct(Connection $db, SchemaBuilder $schemaBuilder, string $projectDir)
    {
        parent::__construct($db, $schemaBuilder);
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     *
     * On SQLite: creates ALL tables from sqlite/schema.sql (converted from MySQL),
     * preserving composite PKs so that all content versions import without conflicts.
     * On MySQL/MariaDB: delegates to the standard SchemaBuilder DDL.
     */
    public function importSchema(): void
    {
        $platform = $this->db->getDatabasePlatform()->getName();

        if ($platform === 'sqlite') {
            $schemaFile = $this->getCjwSqliteFile('schema.sql');
            $this->output->writeln(
                '<info>Creating all tables (eZ Platform + CJW) from <comment>' . $schemaFile . '</comment></info>'
            );
            $this->runQueriesFromFile($schemaFile);
        } else {
            // MySQL / MariaDB: use the standard SchemaBuilder DDL
            parent::importSchema();
        }
    }

    /**
     * {@inheritdoc}
     *
     * On SQLite: loads CJW/Nexus demo content from sqlite/data.sql.
     * On MySQL/MariaDB: falls back to the standard cleandata.sql.
     */
    public function importData(): void
    {
        $platform = $this->db->getDatabasePlatform()->getName();

        if ($platform === 'sqlite') {
            $dataFile = $this->getCjwSqliteFile('data.sql');
            $this->runQueriesFromFile($dataFile);
        } else {
            // MySQL / MariaDB: use the standard eZ Platform clean data
            parent::importData();
        }
    }

    /**
     * Override the parent splitter to additionally convert MySQL-style escape
     * sequences (\\n, \\r) to real characters AFTER splitting on statement
     * boundaries.
     *
     * The conversion must happen post-split: the data.sql dump stores literal
     * two-character sequences "\n" / "\r" inside single-quoted SQL strings.
     * If we converted them before splitting, real newlines would appear inside
     * INSERT statements, potentially triggering the parent's `;$` end-of-line
     * splitter on any semicolons that happen to be followed by the new newline.
     * Post-split conversion is safe because each INSERT is already a complete,
     * fully-isolated statement.
     */
    protected function runQueriesFromFile($file)
    {
        $queries = array_filter(preg_split('(;\s*$)m', file_get_contents($file)));

        if (!$this->output->isQuiet()) {
            $this->output->writeln(
                sprintf(
                    '<info>Executing %d queries from <comment>%s</comment> on database <comment>%s</comment></info>',
                    count($queries),
                    $file,
                    $this->db->getDatabase()
                )
            );
        }

        foreach ($queries as $query) {
            // Convert MySQL dump escape sequences to real characters so that
            // XML values (e.g. ezimage data_text) are stored as valid XML.
            $query = str_replace(['\\n', '\\r'], ["\n", "\r"], $query);
            $this->db->exec($query);
        }
    }

    /**
     * Return the absolute path to a file inside the sqlite/ SQL directory.
     *
     * @throws \RuntimeException if the file is not readable
     */
    private function getCjwSqliteFile(string $filename): string
    {
        $path = $this->projectDir . self::CJW_SQL_DIR . $filename;

        if (!is_readable($path)) {
            throw new \RuntimeException(
                sprintf(
                    'CJW SQLite file not found or not readable: %s' . PHP_EOL .
                    'Run mysql_to_sqlite_cjw.py to generate it.',
                    $path
                )
            );
        }

        return realpath($path);
    }
}
