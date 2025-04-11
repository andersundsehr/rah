<?php

namespace App\Command;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function str_starts_with;

#[AsCommand(
    name: 'version-check',
    description: 'has exit code 0 if you dont need to update',
)]
class VersionCheckCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('availableVersion', InputArgument::REQUIRED, 'Version that could be updated to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $currentVersion = $this->getApplication()?->getVersion() ?? throw new RuntimeException('Current CLI Version not set');

        if (str_starts_with($currentVersion, '@dev_version')) {
            throw new RuntimeException('Current CLI Version not set');
        }

        $availableVersion = $input->getArgument('availableVersion');

        if (version_compare($currentVersion, $availableVersion, '>=')) {
            $io->info(sprintf('Current version %s is up to date (available: %s)', $currentVersion, $availableVersion));
            return Command::SUCCESS;
        }

        $io->caution(sprintf('Current version %s is outdated (available: %s)', $currentVersion, $availableVersion));
        return Command::FAILURE;
    }
}
