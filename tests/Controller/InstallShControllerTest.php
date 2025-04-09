<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InstallShControllerTest extends RahWebTestCase
{
    public function testIndex(): void
    {
        $this->client->request('GET', '/install.sh');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('###RAH_API###', $content);
        $this->assertStringContainsString('http://' . self::TEST_HOSTNAME, $content);
    }

    public function testDiffrentDomain(): void
    {
        $this->client->request('GET', '/install.sh', [],[], ['HTTP_HOST' => 'sub.test.localhost']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testWrongDomain(): void
    {
        $this->client->request('GET', '/install.sh', [],[], ['HTTP_HOST' => 'not-localhost']);

        self::assertResponseStatusCodeSame(500);
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('base domain mismatch: not-localhost does not end with .test.localhost', $content);
    }
}
