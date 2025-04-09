<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function tempnam;

class ApiProjectControllerTest extends RahWebTestCase
{
    public function testDeleteProject(): void
    {
        // Correctly pass the projectName parameter in the DELETE request
        $this->client->request('DELETE', '/api/project?projectName=test_project', [], [], ['CONTENT_TYPE' => 'application/json']);

        // Assert response
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'ok',
            'message' => 'deleted',
        ]), $this->client->getResponse()->getContent());

        // Assert the project directory was deleted
        $this->assertFalse($this->filesystem->exists($this->testProjectPath));
    }

    public function testDeleteProjectNotFound(): void
    {
        // Correctly pass the projectName parameter in the DELETE request
        $this->client->request('DELETE', '/api/project?projectName=test_project_not_found', [], [], ['CONTENT_TYPE' => 'application/json']);

        // Assert response
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'ok',
            'message' => 'already deleted',
        ]), $this->client->getResponse()->getContent());
    }
}
