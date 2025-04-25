<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Override;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function sys_get_temp_dir;
use function uniqid;

abstract class RahWebTestCase extends WebTestCase
{
    protected const API_KEY = 'rah_TEST001AHTN08WVGTC07894NHVG709B8TR39TCM0B4RVN89CMXB3QR78W4CTMV3';

    protected const TEST_HOSTNAME = 'test.localhost';

    protected const TEST_PROJECT_NAME = 'test_project';

    protected KernelBrowser $client;

    protected Filesystem $filesystem;

    protected string $testProjectPath;

    protected function setUp(): void
    {
        $this->testProjectPath = sys_get_temp_dir() . '/storage' . uniqid('', true) . '/' . self::TEST_PROJECT_NAME;
        $_ENV['RAH_STORAGE_PATH'] = dirname($this->testProjectPath);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->testProjectPath);
        $this->filesystem->dumpFile($_ENV['RAH_STORAGE_PATH'] . '/rah-api-key.txt', self::API_KEY);

        $_ENV['RAH_HOSTNAME'] = self::TEST_HOSTNAME;
        $this->client = parent::createClient([], [
            'HTTP_HOST' => self::TEST_HOSTNAME,
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('api:' . self::API_KEY),
        ]);
    }

    /**
     * @param array<mixed> $options
     * @param array<mixed> $server
     */
    #[Override]
    protected static function createClient(array $options = [], array $server = []): KernelBrowser
    {
        throw new RuntimeException('use $this->client instead');
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up the mock project directory
        if ($this->filesystem->exists($_ENV['RAH_STORAGE_PATH'])) {
            $this->filesystem->remove($_ENV['RAH_STORAGE_PATH']);
        }
    }
}
