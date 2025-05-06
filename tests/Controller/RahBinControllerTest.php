<?php

namespace App\Tests\Controller;

use App\Controller\RahBinController;

class RahBinControllerTest extends RahWebTestCase
{
    public function testIndex(): void
    {
        $fileName = $this->testProjectPath . '/rah';
        $this->filesystem->dumpFile($fileName, 'rah-bin-mock-content');
        $this->getContainer()->get(RahBinController::class)->rahBinLocation = $fileName;
        $this->client->request('GET', '/rah');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-disposition', 'attachment; filename=rah');
    }

    public function testSubDomain(): void
    {
        $this->client->request('GET', '/rah', [], [], ['HTTP_HOST' => 'sub.test.localhost']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testWrongDomain(): void
    {
        $this->client->request('GET', '/rah', [], [], ['HTTP_HOST' => 'not-localhost']);

        self::assertResponseStatusCodeSame(500);
    }
}
