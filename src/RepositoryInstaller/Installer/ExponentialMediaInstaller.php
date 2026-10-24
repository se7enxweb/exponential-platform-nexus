<?php

/**
 * @copyright Copyright (C) Exponential Platform Contributors. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace App\RepositoryInstaller\Installer;

use Ibexa\Bundle\RepositoryInstaller\Installer\CoreInstaller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Installer for the Exponential Platform "exponential-media" installation type.
 *
 * Provides a cross-DBMS (MySQL/MariaDB, PostgreSQL, SQLite) alternative to the
 * MySQL-only "netgen-media-legacy" installer. The full schema is built via
 * Doctrine's SchemaBuilder (inherited from CoreInstaller), and platform-specific
 * seed data is loaded from data/{mysql,sqlite,postgresql}/media_data.sql.
 *
 * Usage:
 *   bin/console ibexa:install exponential-media
 *   bin/console exponential:install exponential-media
 */
class ExponentialMediaInstaller extends CoreInstaller
{
    private string $projectDir = '';

    private string $storagePath = 'public/var/site/storage';

    public function setProjectDir(string $projectDir): void
    {
        $this->projectDir = rtrim($projectDir, '/');
        // Override baseDataDir (set in DbBasedInstaller to ibexa/core/data)
        // to point to the project's own data/ directory instead.
        $this->baseDataDir = $this->projectDir . '/data';
    }

    public function setStoragePath(string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }

    /**
     * Extends the core Ibexa schema with extra tables required by the media
     * site: nglayouts_*, eztags_*, novaseo_meta, sckenhancedselection, etc.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function importSchema(): void
    {
        parent::importSchema();
        $this->runQueriesFromFile($this->getKernelSQLFileForDBMS('media_schema.sql'));
    }

    /**
     * Import the media-site demo data using the DBMS-appropriate SQL file.
     *
     * Replaces CoreInstaller::importData() (which loads cleandata.sql)
     * with media_data.sql that includes content, tags and nglayouts
     * configuration for the Netgen Media site demo.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function importData(): void
    {
        $this->runQueriesFromFile($this->getKernelSQLFileForDBMS('media_data.sql'));
        $this->completeLegacyKernelSchema();
    }

    /**
     * The schema and data files describe the legacy kernel they were made with. Adds the tables
     * and columns the installed legacy kernel's own schema (share/db_schema.dba) has on top of
     * them, then those of the active legacy extensions that ship one, for the database engine in
     * use, and gives tables keyed by a part of the legacy key that key (see the completer). Runs
     * after the data, whose INSERT statements carry no column lists, so the added columns come
     * last and take their defaults.
     */
    private function completeLegacyKernelSchema(): void
    {
        $queries = LegacyKernelSchemaCompleter::completeProject($this->db, $this->projectDir);
        $this->output->writeln(\sprintf(
            '<info>Completed the schema with the installed legacy kernel\'s tables, columns and keys: <comment>%d</comment> statement(s)</info>',
            \count($queries),
        ));
    }

    /**
     * Copy the demo storage directory (images, files, …) from the vendor package
     * to the project's public storage path.
     *
     * Silently skipped when the source directory does not exist or when the target
     * already contains files (idempotent re-install safety).
     */
    public function importBinaries(): void
    {
        $source = $this->projectDir . '/vendor/netgen/media-site-data/netgen-media/storage';

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
}
