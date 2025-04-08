<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ProjectService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;

class ProjectServiceTest extends TestCase
{
    private string $tempStorage;
    private Filesystem $filesystem;
    private ProjectService $projectService;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempStorage = sys_get_temp_dir() . '/project_service_test';
        $this->filesystem->mkdir($this->tempStorage);
        putenv('RAH_HOSTNAME=test.localhost');
        $_SERVER['RAH_HOSTNAME'] = 'test.localhost';

        $this->projectService = new ProjectService($this->filesystem, $this->tempStorage);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempStorage);
    }

    public function testGetProjectParts(): void
    {
        $result = $this->projectService->getProjectParts('myproject--mydeployment.test.localhost');

        $this->assertSame(['myproject', 'mydeployment'], $result);
    }

    public function testGetProjectPartsInvalidName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid project name : invalid..name');

        $this->projectService->getProjectParts('invalid..name');
    }

    public function testGetAll(): void
    {
        $request = new Request();

        // Create mock project directories
        $this->filesystem->mkdir($this->tempStorage . '/project1');
        $this->filesystem->mkdir($this->tempStorage . '/project2');

        $projects = $this->projectService->getAll($request);

        $this->assertCount(2, $projects);
        $this->assertArrayHasKey('project1', $projects);
        $this->assertArrayHasKey('project2', $projects);
    }
}
