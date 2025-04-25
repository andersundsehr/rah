<?php

namespace App\Command;

use App\Dto\Size;
use App\Service\FileSizeService;
use App\Service\ProjectService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function array_keys;
use function array_shift;

#[AsCommand(
    name: 'background:cleanup-old-deployments',
    description: 'Add a short description for your command',
)]
final class BackgroundCleanupOldDeploymentsCommand extends Command
{
    private readonly int $maxBytes;

    public function __construct(
        private readonly ProjectService $projectService,
        private readonly RequestStack $requestStack,
        string $rahMaxDiskUsage,
        private readonly FileSizeService $fileSizeService,
    ) {
        parent::__construct();
        $this->maxBytes = $this->fileSizeService->convertToBytes($rahMaxDiskUsage);
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', 'd', null, 'Do not delete anything, just show what would be deleted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=white;bg=magenta>RAH Background Cleanup Old Deployments:</>');

        $io = new SymfonyStyle($input, $output);
        $this->requestStack->push(new Request());
        $projects = $this->projectService->loadAll();
        $deployments = [];

        $totalSize = 0;

        foreach ($projects as $project) {
            foreach ($project->deployments as $deployment) {
                $totalSize += $deployment->size->bytes;
                $isDefault = (string)(int)($deployment->deploymentSettings->defaultDeployment === $deployment->name);
                $deployments[$isDefault . '-' . $deployment->lastUpdate->getTimestamp() . '-' . $project->name . '--' . $deployment->name] = $deployment;
            }
        }

        ksort($deployments, SORT_NATURAL);

        if ($totalSize <= $this->maxBytes) {
            $message = 'No cleanup needed. Total size: ' . Size::formatSize($totalSize) . ' / ' . Size::formatSize($this->maxBytes);
            $output->writeln('<fg=green>' . $message . '</>');
            return Command::SUCCESS;
        }

        $printRows = [];
        foreach ($deployments as $deployment) {
            $printRows[] = [
                'project' => $deployment->project->name,
                'name' => $deployment->name,
                'default' => $deployment->deploymentSettings->defaultDeployment === $deployment->name,
                'size' => $deployment->size->humanReadable,
                'lastUpdate' => $deployment->lastUpdate->format('Y-m-d H:i:s'),
            ];
        }

        if ($printRows) {
            $io->table(array_keys($printRows[0]), $printRows);
        }

        $output->writeln('before: ' . Size::formatSize($totalSize) . ' allowed: ' . Size::formatSize($this->maxBytes));

        while ($totalSize > $this->maxBytes && $deployments) {
            $deploymentToDelete = array_shift($deployments);
            $io->caution('Deleting deployment: ' . $deploymentToDelete->project->name . ' ' . $deploymentToDelete->name . ' freeing up ' . $deploymentToDelete->size->humanReadable);
            if (!$input->getOption('dry-run')) {
                $this->projectService->deleteDeployment($deploymentToDelete);
            }

            $totalSize -= $deploymentToDelete->size->bytes;
        }

        $message = 'DONE. Total size left: ' . Size::formatSize($totalSize) . ' / ' . Size::formatSize($this->maxBytes);
        $output->writeln('<fg=green>' . $message . '</>');

        return Command::SUCCESS;
    }
}
