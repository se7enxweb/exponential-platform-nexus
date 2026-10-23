<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Proxy command that delegates to ibexa:reindex.
 * Falls back gracefully when the command is not registered (e.g. search engine missing).
 *
 * On SQLite the index is written by a single process: SQLite allows one writer at a time, and
 * the parallel child processes that ibexa:reindex starts by default fail with
 * "database is locked", leaving most content out of the legacy search index.
 */
final class ExponentialReindexCommand extends Command
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('exponential:reindex')
            ->setDescription('Re-indexes the search engine (delegates to ibexa:reindex).')
            ->addOption('siteaccess', null, InputOption::VALUE_OPTIONAL, 'SiteAccess to use')
            ->addOption(
                'processes',
                null,
                InputOption::VALUE_OPTIONAL,
                'Child processes for ibexa:reindex; default "auto", or "1" on SQLite, which allows one writer only'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $command = $this->getApplication()->find('ibexa:reindex');
        } catch (CommandNotFoundException $e) {
            $output->writeln('<comment>ibexa:reindex is not registered — skipping (search engine may not require explicit reindexing).</comment>');

            return Command::SUCCESS;
        }

        $args = ['command' => 'ibexa:reindex'];
        if ($siteaccess = $input->getOption('siteaccess')) {
            $args['--siteaccess'] = $siteaccess;
        }

        $processes = $input->getOption('processes');
        if ($processes === null && $this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $processes = '1';
            $output->writeln('SQLite detected — re-indexing in one process (SQLite allows one writer at a time).');
        }
        if ($processes !== null) {
            $args['--processes'] = $processes;
        }

        $childInput = new ArrayInput($args);
        $childInput->setInteractive(false);

        return $command->run($childInput, $output);
    }
}
