<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Settings;
use Override;
use App\Dto\Project;
use App\Service\ProjectService;
use RuntimeException;

use function json_encode;

use const JSON_PRETTY_PRINT;

class ProjectServiceTest extends RahKernelTestcase
{
    private ProjectService $projectService;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->projectService = self::getContainer()->get(ProjectService::class);
    }

    public function testGetProjectParts(): void
    {
        $result = $this->projectService->getProjectParts('myproject--mydeployment.test.localhost');

        $this->assertSame(['myproject', 'mydeployment'], $result);
    }

    public function testGetProjectPartsInvalidName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid project name: invalid.name');

        $this->projectService->getProjectParts('invalid.name.test.localhost');
    }

    public function testGetProjectPartsWrongDomain(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('base domain mismatch: not-localhost does not end with .test.localhost');

        $this->projectService->getProjectParts('not-localhost');
    }

    public function testLoadAll(): void
    {
        // Create mock project directories
        $this->filesystem->mkdir($this->tempStorage . '/project1/deployment1');
        $this->filesystem->dumpFile($this->tempStorage . '/project1/deployment1/deployment.json', \Safe\json_encode(new Settings(
            api: 'http://example.com',
            projectName: 'project1',
            deployment: 'deployment1',
            deploymentMessage: 'Test deployment',
            defaultDeployment: 'default',
            deleteAfter: Settings::DEFAULT_DELETE_AFTER,
            deleteIfMissingBranch: '',
        ), JSON_PRETTY_PRINT));
        $this->filesystem->mkdir($this->tempStorage . '/project2/deployment1');
        $this->filesystem->dumpFile($this->tempStorage . '/project2/deployment1/deployment.json', \Safe\json_encode(new Settings(
            api: 'http://example.com',
            projectName: 'project1',
            deployment: 'deployment1',
            deploymentMessage: 'Test deployment',
            defaultDeployment: 'default',
            deleteAfter: Settings::DEFAULT_DELETE_AFTER,
            deleteIfMissingBranch: '',
        ), JSON_PRETTY_PRINT));

        $projects = $this->projectService->loadAll();

        $this->assertCount(2, $projects);
        $this->assertArrayHasKey('project1', $projects);
        $this->assertInstanceOf(Project::class, $projects['project1']);
        $this->assertArrayHasKey('project2', $projects);
        $this->assertInstanceOf(Project::class, $projects['project2']);
    }
}
