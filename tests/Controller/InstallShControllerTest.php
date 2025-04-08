<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InstallShControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        // Mock the RAH_HOSTNAME constant
        $client->setServerParameter('HTTP_HOST', 'rah.localhost');

        $crawler = $client->request('GET', '/install.sh', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');

        $content = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('###RAH_API###', $content);
        $this->assertStringContainsString('rah.localhost', $content);
    }
}
