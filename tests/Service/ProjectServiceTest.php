<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Override;
use App\Dto\Project;
use App\Service\ProjectService;
use RuntimeException;

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
        $this->filesystem->mkdir($this->tempStorage . '/project1');
        $this->filesystem->mkdir($this->tempStorage . '/project2');

        $projects = $this->projectService->loadAll();

        $this->assertCount(2, $projects);
        $this->assertArrayHasKey('project1', $projects);
        $this->assertInstanceOf(Project::class, $projects['project1']);
        $this->assertArrayHasKey('project2', $projects);
        $this->assertInstanceOf(Project::class, $projects['project2']);
    }
}
