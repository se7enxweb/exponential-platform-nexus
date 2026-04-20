<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Proxy command that delegates to ibexa:reindex.
 * Falls back gracefully when the command is not registered (e.g. search engine missing).
 */
final class ExponentialReindexCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('exponential:reindex')
            ->setDescription('Re-indexes the search engine (delegates to ibexa:reindex).')
            ->addOption('siteaccess', null, InputOption::VALUE_OPTIONAL, 'SiteAccess to use');
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

        $childInput = new ArrayInput($args);
        $childInput->setInteractive(false);

        return $command->run($childInput, $output);
    }
}
