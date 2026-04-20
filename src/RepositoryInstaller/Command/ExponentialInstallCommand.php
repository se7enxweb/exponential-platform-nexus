<?php

/**
 * @copyright Copyright (C) Exponential Platform Contributors. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace App\RepositoryInstaller\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Ibexa\Bundle\Core\ApiLoader\RepositoryConfigurationProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Backported v5 install command for v4 (Ibexa 4.6 / Symfony 5.4).
 *
 * Differences from the upstream ibexa:install command:
 *  - Named exponential:install (avoids duplicate-command conflict)
 *  - checkCreateDatabase() skips doctrine:database:create for SQLite
 *    (SQLite creates the file automatically on first DBAL connect;
 *    doctrine:database:create would throw DBALException on getListDatabasesSQL)
 *
 * Usage:
 *   bin/console exponential:install exponential-media [--no-interaction]
 */
final class ExponentialInstallCommand extends Command
{
    public const EXIT_GENERAL_DATABASE_ERROR = 4;
    public const EXIT_PARAMETERS_NOT_FOUND = 5;
    public const EXIT_UNKNOWN_INSTALL_TYPE = 6;
    public const EXIT_MISSING_PERMISSIONS = 7;

    private Connection $connection;

    private OutputInterface $output;

    private CacheItemPoolInterface $cachePool;

    private string $environment;

    /** @var \Ibexa\Bundle\RepositoryInstaller\Installer\Installer[] */
    private array $installers = [];

    private RepositoryConfigurationProvider $repositoryConfigurationProvider;

    public function __construct(
        Connection $connection,
        array $installers,
        CacheItemPoolInterface $cachePool,
        string $environment,
        RepositoryConfigurationProvider $repositoryConfigurationProvider
    ) {
        $this->connection = $connection;
        $this->installers = $installers;
        $this->cachePool = $cachePool;
        $this->environment = $environment;
        $this->repositoryConfigurationProvider = $repositoryConfigurationProvider;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('exponential:install');
        $this->addArgument(
            'type',
            InputArgument::OPTIONAL,
            'The type of install. Available options: ' . implode(', ', array_keys($this->installers)),
            'ibexa-oss',
        );
        $this->addOption(
            'skip-indexing',
            null,
            InputOption::VALUE_NONE,
            'Skip indexing (exponential:reindex)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->checkPermissions();
        $this->checkCreateDatabase($output);

        $schemaManager = $this->connection->getSchemaManager();
        if (!empty($schemaManager->listTables())) {
            $io = new SymfonyStyle($input, $output);
            if (!$io->confirm('Running this command will delete data in all Ibexa generated tables. Continue?')) {
                return 0;
            }
        }

        $type = $input->getArgument('type');
        $siteaccess = $input->getOption('siteaccess');
        $installer = $this->getInstaller($type);
        if ($installer === false) {
            $output->writeln(
                "Unknown install type '$type', available options in currently installed Ibexa package: " .
                implode(', ', array_keys($this->installers)),
            );
            exit(self::EXIT_UNKNOWN_INSTALL_TYPE);
        }

        $installer->setOutput($output);

        $installer->importSchema();
        $installer->importData();
        $installer->importBinaries();
        $this->cacheClear($output);

        if (!$input->getOption('skip-indexing')) {
            $this->indexData($output, $siteaccess);
        }

        return 0;
    }

    private function checkPermissions(): void
    {
        if (!is_writable('public') && !is_writable('public/var')) {
            $this->output->writeln('[public/ | public/var] is not writable');
            exit(self::EXIT_MISSING_PERMISSIONS);
        }
    }

    private function checkCreateDatabase(OutputInterface $output): void
    {
        // SQLite: the .db file is created automatically on first DBAL connect;
        // listDatabases() is not supported by SQLite so doctrine:database:create
        // would throw DBALException. Skip it entirely.
        if ($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $output->writeln(
                \sprintf(
                    'SQLite detected — skipping doctrine:database:create (<comment>%s</comment> will be created automatically).',
                    $this->connection->getDatabase(),
                ),
            );

            return;
        }

        $output->writeln(
            \sprintf(
                'Creating database <comment>%s</comment> if it does not exist, using doctrine:database:create --if-not-exists',
                $this->connection->getDatabase(),
            ),
        );

        try {
            $bufferedOutput = new BufferedOutput();
            $connectionName = $this->repositoryConfigurationProvider->getStorageConnectionName();
            $command = \sprintf('doctrine:database:create --if-not-exists --connection=%s', $connectionName);
            $this->executeCommand($bufferedOutput, $command);
            $output->writeln($bufferedOutput->fetch());
        } catch (\RuntimeException $exception) {
            $this->output->writeln(
                \sprintf(
                    "<error>The configured database '%s' does not exist or cannot be created (%s).</error>",
                    $this->connection->getDatabase(),
                    $exception->getMessage(),
                ),
            );
            exit(self::EXIT_GENERAL_DATABASE_ERROR);
        }
    }

    private function cacheClear(OutputInterface $output): void
    {
        $this->cachePool->clear();
    }

    private function indexData(OutputInterface $output, ?string $siteaccess = null): void
    {
        $output->writeln('Search engine re-indexing, executing command exponential:reindex');

        $command = 'exponential:reindex';
        if ($siteaccess) {
            $command .= \sprintf(' --siteaccess=%s', $siteaccess);
        }

        $this->executeCommand($output, $command);
    }

    /**
     * @return \Ibexa\Bundle\RepositoryInstaller\Installer\Installer|false
     */
    private function getInstaller(string $type)
    {
        if (!isset($this->installers[$type])) {
            return false;
        }

        return $this->installers[$type];
    }

    private function executeCommand(OutputInterface $output, string $cmd, int $timeout = 300): void
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find(false)) {
            throw new \RuntimeException('The php executable could not be found. Add it to your PATH environment variable and try again');
        }

        $arguments = $phpFinder->findArguments();
        if (false !== ($ini = php_ini_loaded_file())) {
            $arguments[] = '--php-ini=' . $ini;
        }

        if ($memoryLimit = ini_get('memory_limit')) {
            $arguments[] = '-d memory_limit=' . $memoryLimit;
        }

        $phpArgs = implode(' ', array_map('escapeshellarg', $arguments));
        $php = escapeshellarg($phpPath) . ($phpArgs ? ' ' . $phpArgs : '');

        $console = escapeshellarg('bin/console');
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $console .= ' -' . str_repeat('v', $output->getVerbosity() - 1);
        }

        if ($output->isDecorated()) {
            $console .= ' --ansi';
        }

        $console .= ' --env=' . escapeshellarg($this->environment);

        $process = Process::fromShellCommandline(
            implode(' ', [$php, $console, $cmd]),
            null,
            null,
            null,
            $timeout,
        );

        $process->run(static function ($type, $buffer) use ($output): void {
            $output->write($buffer, false);
        });
    }
}
