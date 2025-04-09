<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function sys_get_temp_dir;
use function uniqid;

abstract class RahWebTestCase extends WebTestCase
{
    protected const TEST_HOSTNAME = 'test.localhost';
    protected const TEST_PROJECT_NAME = 'test_project';

    protected KernelBrowser $client;
    protected Filesystem $filesystem;
    protected string $testProjectPath;

    public function setUp(): void
    {
        $this->testProjectPath = sys_get_temp_dir() . '/storage' . uniqid('', true) . '/' . self::TEST_PROJECT_NAME;
        $_ENV['RAH_STORAGE_PATH'] = dirname($this->testProjectPath);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->testProjectPath);

        $_ENV['RAH_HOSTNAME'] = self::TEST_HOSTNAME;
        $this->client = WebTestCase::createClient([], ['HTTP_HOST' => self::TEST_HOSTNAME, 'HTTP_ACCEPT' => 'application/json']);
    }

    public static function createClient(array $options = [], array $server = []): KernelBrowser
    {
        throw new \RuntimeException('use $this->client instead');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        // Clean up the mock project directory
        if ($this->filesystem->exists($_ENV['RAH_STORAGE_PATH'])) {
            $this->filesystem->remove($_ENV['RAH_STORAGE_PATH']);
        }
    }
}
