<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function sys_get_temp_dir;
use function uniqid;

abstract class RahKernelTestcase extends KernelTestCase
{
    protected string $tempStorage;

    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempStorage = sys_get_temp_dir() . '/storage' . uniqid('', true) . '/';
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempStorage);
        $_ENV['RAH_STORAGE_PATH'] = $this->tempStorage;

        $_ENV['RAH_HOSTNAME'] = 'test.localhost';
        $requestStack = self::getContainer()->get(RequestStack::class);

        do {
            // reset the stack
            $x = $requestStack->pop();
        } while ($x);

        $requestStack->push(new Request([], [], [], [], [], [
            'HTTP_HOST' => $_ENV['RAH_HOSTNAME'],
            'SERVER_NAME' => $_ENV['RAH_HOSTNAME'],
            'SERVER_PORT' => 80,
            'REQUEST_SCHEME' => 'http',
        ]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->filesystem->exists($_ENV['RAH_STORAGE_PATH'])) {
            $this->filesystem->remove($_ENV['RAH_STORAGE_PATH']);
        }
    }
}
