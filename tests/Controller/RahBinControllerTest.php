<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RahBinControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();

        // Mock the RAH_HOSTNAME constant
        $client->setServerParameter('HTTP_HOST', 'rah.localhost');

        $client->request('GET', '/rah', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename=rah');
    }
}
