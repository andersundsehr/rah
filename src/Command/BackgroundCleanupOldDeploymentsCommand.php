<?php

namespace App\Command;

use App\Dto\Deployment;
use App\Dto\Size;
use App\Service\FileSizeService;
use App\Service\ProjectService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function array_shift;
use function assert;

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
        #[Autowire(env:'RAH_MAX_DISK_USAGE')]
        string $rahMaxDiskUsage,
        private readonly FileSizeService $fileSizeService,
    ) {
        parent::__construct();
        $this->maxBytes = $this->fileSizeService->convertToBytes($rahMaxDiskUsage);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

        $io->text('before: ' . Size::formatSize($totalSize) . ' allowed: ' . Size::formatSize($this->maxBytes));

        while ($totalSize > $this->maxBytes && $deployments) {
            $deploymentToDelete = array_shift($deployments);
            assert($deploymentToDelete instanceof Deployment);
            $io->caution('Deleting deployment: ' . $deploymentToDelete->project->name . '--' . $deploymentToDelete->name . ' freeing up ' . $deploymentToDelete->size->humanReadable);
            $this->projectService->deleteDeployment($deploymentToDelete);
            $totalSize -= $deploymentToDelete->size->bytes;
        }

        $io->success('DONE. Total size left: ' . Size::formatSize($totalSize) . ' / ' . Size::formatSize($this->maxBytes));

        return Command::SUCCESS;
    }
}
