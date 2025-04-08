<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use App\Dto\Project;

class ApiProjectControllerTest extends WebTestCase
{
    public function testDeleteProject(): void
    {
        $client = static::createClient();

        // Set the base path to the system's temporary directory for testing
        Project::setBasePath(sys_get_temp_dir());

        // Create a mock project directory
        $filesystem = new Filesystem();
        $projectPath = sys_get_temp_dir() . '/test_project';
        $filesystem->mkdir($projectPath);

        // Ensure the directory exists before the request
        $this->assertDirectoryExists($projectPath);

        // Send DELETE request to the API with query parameters
        $client->request('DELETE', '/api/project?projectName=test_project', [], [], ['HTTP_ACCEPT' => 'application/json']);

        // Assert the response is successful
        $this->assertResponseIsSuccessful();

        // Assert the response contains the expected JSON
        $responseContent = $client->getResponse()->getContent();
        $this->assertJson($responseContent);
        $responseData = json_decode($responseContent, true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('ok', $responseData['status']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('deleted', $responseData['message']);

        // Assert the directory has been removed
        $this->assertDirectoryDoesNotExist($projectPath);
    }

    public function testDeleteProjectWithValidName(): void
    {
        $client = static::createClient();

        // Set the base path to the system's temporary directory for testing
        Project::setBasePath(sys_get_temp_dir());

        // Create a mock project directory
        $filesystem = new Filesystem();
        $projectPath = sys_get_temp_dir() . '/test_project_valid';
        $filesystem->mkdir($projectPath);

        // Ensure the directory exists before the request
        $this->assertDirectoryExists($projectPath);

        // Send DELETE request to the API with query parameters
        $client->request('DELETE', '/api/project?projectName=test_project_valid', [], [], ['HTTP_ACCEPT' => 'application/json']);

        // Assert the response is successful
        $this->assertResponseIsSuccessful();

        // Assert the response contains the expected JSON
        $responseContent = $client->getResponse()->getContent();
        $this->assertJson($responseContent);
        $responseData = json_decode($responseContent, true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('ok', $responseData['status']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('deleted', $responseData['message']);

        // Assert the directory has been removed
        $this->assertDirectoryDoesNotExist($projectPath);
    }

    public function testDeleteProjectNotFound(): void
    {
        $client = static::createClient();

        // Set the base path to the system's temporary directory for testing
        Project::setBasePath(sys_get_temp_dir());

        // Ensure the directory does not exist
        $projectPath = sys_get_temp_dir() . '/non_existent_project';
        $this->assertDirectoryDoesNotExist($projectPath);

        // Send DELETE request to the API with a non-existent project
        $client->request('DELETE', '/api/project?projectName=non_existent_project', [], [], ['HTTP_ACCEPT' => 'application/json']);

        // Assert the response status code is 404
        $this->assertResponseStatusCodeSame(404);

        // Assert the response contains the expected JSON error message
        $responseContent = $client->getResponse()->getContent();
        $this->assertJson($responseContent);
        $responseData = json_decode($responseContent, true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals(404, $responseData['status']);
        $this->assertArrayHasKey('detail', $responseData);
        $this->assertStringContainsString('Project not found', $responseData['detail']);
    }
}
