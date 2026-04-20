<?php

declare(strict_types=1);

namespace App\Installer;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use EzSystems\PlatformInstallerBundle\Installer\CoreInstaller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Installer for the Exponential Platform "exponential-media" installation type.
 *
 * Provides a cross-DBMS (MySQL/MariaDB, PostgreSQL, SQLite) alternative to the
 * MySQL-only "netgen-media" installer. The full schema is built via Doctrine's
 * SchemaBuilder (inherited from CoreInstaller), and platform-specific seed data
 * is loaded from data/{sqlite,mysql,postgresql}/media_data.sql.
 *
 * SQLite-specific behaviour:
 *  - After schema creation, fixSqliteCompositePrimaryKeys() recreates the three
 *    tables that Doctrine collapses to single-column INTEGER PRIMARY KEY back to
 *    composite PRIMARY KEY (id, version), matching MySQL/PostgreSQL.
 *  - Reads data from data/sqlite/media_data.sql instead of the eZ kernel's
 *    cleandata.sql.
 *
 * Usage:
 *   bin/console exponential:install exponential-media
 */
class ExponentialMediaInstaller extends CoreInstaller
{
    /** @var string */
    private $projectDir = '';

    /** @var string */
    private $storagePath = 'public/var/site/storage';

    public function setProjectDir(string $projectDir): void
    {
        $this->projectDir = rtrim($projectDir, '/');
        // Override baseDataDir (DbBasedInstaller default: eZ kernel data dir)
        $this->baseDataDir = $this->projectDir . '/data';
    }

    public function setStoragePath(string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }

    public function importSchema(): void
    {
        parent::importSchema();
        $this->runQueriesFromFile($this->getKernelSQLFileForDBMS('media_schema.sql'));
        $this->fixSqliteCompositePrimaryKeys();
    }

    /**
     * On SQLite, Doctrine collapses composite PRIMARY KEYs that include an
     * INTEGER column into a single-column `id INTEGER PRIMARY KEY`.
     * This breaks Ibexa's versioned tables where the same `id` must appear
     * with multiple `version` values (e.g. ezcontentobject_attribute).
     *
     * Recreates the three affected tables with the correct composite PRIMARY KEY
     * (id, version) before data import. No-op on MySQL and PostgreSQL.
     */
    private function fixSqliteCompositePrimaryKeys(): void
    {
        $platform = $this->db->getDatabasePlatform();
        if (!$platform instanceof SqlitePlatform) {
            return;
        }

        $fixes = [
            'ezcontentobject_attribute' => [
                'create' => <<<SQL
                    CREATE TABLE ezcontentobject_attribute (
                        id INTEGER NOT NULL,
                        version INTEGER DEFAULT 0 NOT NULL,
                        attribute_original_id INTEGER DEFAULT 0,
                        contentclassattribute_id INTEGER DEFAULT 0 NOT NULL,
                        contentobject_id INTEGER DEFAULT 0 NOT NULL,
                        data_float DOUBLE PRECISION DEFAULT NULL,
                        data_int INTEGER DEFAULT NULL,
                        data_text CLOB DEFAULT NULL,
                        data_type_string VARCHAR(50) DEFAULT '',
                        language_code VARCHAR(20) DEFAULT '' NOT NULL,
                        language_id BIGINT DEFAULT 0 NOT NULL,
                        sort_key_int INTEGER DEFAULT 0 NOT NULL,
                        sort_key_string VARCHAR(255) DEFAULT '' NOT NULL,
                        PRIMARY KEY (id, version)
                    )
                    SQL,
                'indexes' => [
                    'CREATE INDEX ezcontentobject_attribute_co_id_ver_lang_code ON ezcontentobject_attribute (contentobject_id, version, language_code)',
                    'CREATE INDEX ezcontentobject_classattr_id ON ezcontentobject_attribute (contentclassattribute_id)',
                    'CREATE INDEX sort_key_string ON ezcontentobject_attribute (sort_key_string)',
                    'CREATE INDEX ezcontentobject_attribute_language_code ON ezcontentobject_attribute (language_code)',
                    'CREATE INDEX sort_key_int ON ezcontentobject_attribute (sort_key_int)',
                    'CREATE INDEX ezcontentobject_attribute_co_id_ver ON ezcontentobject_attribute (contentobject_id, version)',
                ],
            ],
            'ezcontentclass' => [
                'create' => <<<SQL
                    CREATE TABLE ezcontentclass (
                        id INTEGER NOT NULL,
                        version INTEGER DEFAULT 0 NOT NULL,
                        always_available INTEGER DEFAULT 0 NOT NULL,
                        contentobject_name VARCHAR(255) DEFAULT NULL,
                        created INTEGER DEFAULT 0 NOT NULL,
                        creator_id INTEGER DEFAULT 0 NOT NULL,
                        identifier VARCHAR(50) DEFAULT '' NOT NULL,
                        initial_language_id BIGINT DEFAULT 0 NOT NULL,
                        is_container INTEGER DEFAULT 0 NOT NULL,
                        language_mask BIGINT DEFAULT 0 NOT NULL,
                        modified INTEGER DEFAULT 0 NOT NULL,
                        modifier_id INTEGER DEFAULT 0 NOT NULL,
                        remote_id VARCHAR(100) DEFAULT '' NOT NULL,
                        serialized_description_list CLOB DEFAULT NULL,
                        serialized_name_list CLOB DEFAULT NULL,
                        sort_field INTEGER DEFAULT 1 NOT NULL,
                        sort_order INTEGER DEFAULT 1 NOT NULL,
                        url_alias_name VARCHAR(255) DEFAULT NULL,
                        PRIMARY KEY (id, version)
                    )
                    SQL,
                'indexes' => [
                    'CREATE INDEX ezcontentclass_version ON ezcontentclass (version)',
                    'CREATE INDEX ezcontentclass_identifier ON ezcontentclass (identifier, version)',
                ],
            ],
            'ezcontentclass_attribute' => [
                'create' => <<<SQL
                    CREATE TABLE ezcontentclass_attribute (
                        id INTEGER NOT NULL,
                        version INTEGER DEFAULT 0 NOT NULL,
                        can_translate INTEGER DEFAULT 1,
                        category VARCHAR(25) DEFAULT '' NOT NULL,
                        contentclass_id INTEGER DEFAULT 0 NOT NULL,
                        data_float1 DOUBLE PRECISION DEFAULT NULL,
                        data_float2 DOUBLE PRECISION DEFAULT NULL,
                        data_float3 DOUBLE PRECISION DEFAULT NULL,
                        data_float4 DOUBLE PRECISION DEFAULT NULL,
                        data_int1 INTEGER DEFAULT NULL,
                        data_int2 INTEGER DEFAULT NULL,
                        data_int3 INTEGER DEFAULT NULL,
                        data_int4 INTEGER DEFAULT NULL,
                        data_text1 VARCHAR(255) DEFAULT NULL,
                        data_text2 VARCHAR(50) DEFAULT NULL,
                        data_text3 VARCHAR(50) DEFAULT NULL,
                        data_text4 VARCHAR(255) DEFAULT NULL,
                        data_text5 CLOB DEFAULT NULL,
                        data_type_string VARCHAR(50) DEFAULT '' NOT NULL,
                        identifier VARCHAR(50) DEFAULT '' NOT NULL,
                        is_information_collector INTEGER DEFAULT 0 NOT NULL,
                        is_required INTEGER DEFAULT 0 NOT NULL,
                        is_searchable INTEGER DEFAULT 0 NOT NULL,
                        is_thumbnail BOOLEAN DEFAULT '0' NOT NULL,
                        placement INTEGER DEFAULT 0 NOT NULL,
                        serialized_data_text CLOB DEFAULT NULL,
                        serialized_description_list CLOB DEFAULT NULL,
                        serialized_name_list CLOB NOT NULL,
                        PRIMARY KEY (id, version)
                    )
                    SQL,
                'indexes' => [
                    'CREATE INDEX ezcontentclass_attr_ccid ON ezcontentclass_attribute (contentclass_id)',
                    'CREATE INDEX ezcontentclass_attr_dts ON ezcontentclass_attribute (data_type_string)',
                ],
            ],
        ];

        foreach ($fixes as $table => $def) {
            $tmp = $table . '_cpkfix';
            $this->db->executeStatement('DROP TABLE IF EXISTS ' . $tmp);
            $this->db->executeStatement(str_replace($table, $tmp, $def['create']));
            $this->db->executeStatement(sprintf('INSERT INTO %s SELECT * FROM %s', $tmp, $table));
            $this->db->executeStatement('DROP TABLE ' . $table);
            $this->db->executeStatement(sprintf('ALTER TABLE %s RENAME TO %s', $tmp, $table));
            foreach ($def['indexes'] as $idx) {
                $this->db->executeStatement($idx);
            }
            $this->output->writeln(sprintf(
                ' <info>SQLite composite PK fixed:</info> %s',
                $table,
            ));
        }
    }

    public function importData(): void
    {
        $this->runQueriesFromFile($this->getKernelSQLFileForDBMS('media_data.sql'));
    }

    public function importBinaries(): void
    {
        $source = $this->projectDir . '/vendor/se7enxweb/media-site-data/netgen-media/storage';

        if (!\is_dir($source)) {
            return;
        }

        $target = $this->projectDir . '/' . \ltrim($this->storagePath, '/');
        $fs = new Filesystem();

        if ($fs->exists($target)) {
            $finder = (new Finder())
                ->ignoreDotFiles(false)
                ->ignoreVCS(false)
                ->ignoreUnreadableDirs(false)
                ->in($target);

            if ($finder->count() > 0) {
                $this->output->writeln(
                    \sprintf(
                        '<comment>Storage directory <info>%s</info> already exists and is not empty, skipping creation...</comment>',
                        $target,
                    ),
                );

                return;
            }

            $fs->remove($target);
        }

        $this->output->writeln(
            \sprintf('Copying storage directory to <info>%s</info>', $target),
        );

        $fs->mirror($source, $target);
    }

    public function createConfiguration(): void
    {
    }
}
