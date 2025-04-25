<?php

namespace App\Tests\Controller;

use function base64_encode;

class ApiProjectControllerTest extends RahWebTestCase
{
    public function testDeleteProject(): void
    {
        // Correctly pass the projectName parameter in the DELETE request
        $this->client->request('DELETE', '/api/project?projectName=test_project', [], [], []);

        // Assert response
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(json_encode([
            'status' => 'ok',
            'message' => 'deleted',
        ], JSON_THROW_ON_ERROR), (string)$this->client->getResponse()->getContent());

        // Assert the project directory was deleted
        $this->assertFalse($this->filesystem->exists($this->testProjectPath));
    }

    public function testDeleteProjectWithWrongToken(): void
    {
        // Correctly pass the projectName parameter in the DELETE request
        $this->client->request('DELETE', '/api/project?projectName=test_project', [], [], [
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('api:api'),
            ]);

        // Assert response
        self::assertResponseStatusCodeSame(401);

        // Assert the project directory was deleted
        $this->assertTrue($this->filesystem->exists($this->testProjectPath));
    }

    public function testDeleteProjectNotFound(): void
    {
        // Correctly pass the projectName parameter in the DELETE request
        $this->client->request('DELETE', '/api/project?projectName=test_project_not_found', [], [], []);

        // Assert response
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(json_encode([
            'status' => 'ok',
            'message' => 'already deleted',
        ], JSON_THROW_ON_ERROR), (string)$this->client->getResponse()->getContent());
    }
}
