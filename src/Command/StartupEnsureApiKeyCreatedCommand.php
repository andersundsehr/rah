<?php

namespace App\Command;

use App\Service\ApiKeyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'startup:ensure-api-key-created',
    description: 'Add a short description for your command',
)]
final class StartupEnsureApiKeyCreatedCommand extends Command
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force-recreate', 'f', InputOption::VALUE_NONE, 'Force Recreate the api key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $output->writeln('<fg=white;bg=magenta>                       </>');
        $output->writeln('<fg=white;bg=magenta>                       </>');
        $output->writeln('<fg=white;bg=magenta>                       </>');
        $output->writeln('<fg=white;bg=magenta>                       </>');
        $output->writeln('<fg=white;bg=magenta>                       </>');
        $output->writeln('<fg=white;bg=magenta>██████▄ ▄█████▄ ██   ██</>');
        $output->writeln('<fg=white;bg=magenta>██   ██ ██   ██ ██   ██</>');
        $output->writeln('<fg=white;bg=magenta>██████  ███████ ███████</>');
        $output->writeln('<fg=white;bg=magenta>██   ██ ██   ██ ██   ██</>');
        $output->writeln('<fg=white;bg=magenta>██   ██ ██   ██ ██   ██</>');

        $forced = (bool)$input->getOption('force-recreate');
        $created = $this->apiKeyService->createIfNeeded($forced);
        if ($created) {
            $io->success('API key created ' . ($forced ? 'forced!' : '(should only be created on first startup)'));
        }

        $io->note('API key: ' . $this->apiKeyService->getKey() . PHP_EOL . 'stored in ' . $this->apiKeyService->getFilename());

        return Command::SUCCESS;
    }
}
