<?php

declare(strict_types=1);

namespace App\Command;

use App\Installer\LegacyKernelSchemaCompleter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Brings an existing database up to the installed legacy kernel's schema: the tables, columns and
 * primary keys exponential:install completes on a new installation (see LegacyKernelSchemaCompleter).
 *
 * Run it after updating the legacy kernel, and once on every SQLite installation made before
 * v1.1.0.11: tables such as ezworkflow, ezworkflow_event, ezenumvalue or ezrss_export were keyed
 * by their id alone, so a new version of a row replaced the one before it. The rows are kept when
 * the key is changed; rows that were replaced before cannot be restored. Back up the database first.
 */
final class LegacySchemaCompleteCommand extends Command
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /** @var string */
    private $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('exponential:legacy:complete-schema')
            ->setDescription('Adds the legacy kernel\'s missing tables, columns and primary keys to the database, keeping its rows.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queries = LegacyKernelSchemaCompleter::completeProject($this->connection, $this->projectDir);
        $output->writeln(\sprintf('<info>%d statement(s) executed.</info>', \count($queries)));

        return Command::SUCCESS;
    }
}
