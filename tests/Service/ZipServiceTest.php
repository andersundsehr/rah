<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ZipService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ZipServiceTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/zip_service_test';
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testUnzip(): void
    {
        $zipService = new ZipService();

        // Create a temporary zip file
        $zipFileName = $this->tempDir . '/test.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFileName, \ZipArchive::CREATE);
        $zip->addFromString('test.txt', 'This is a test file.');
        $zip->close();

        // Define the extraction path
        $extractionPath = $this->tempDir . '/extracted';

        // Call the unzip method
        $zipService->unzip($zipFileName, $extractionPath, false);

        // Assert the file was extracted
        $this->assertDirectoryExists($extractionPath);
        $this->assertFileExists($extractionPath . '/test.txt');
        $this->assertStringEqualsFile($extractionPath . '/test.txt', 'This is a test file.');
    }

    public function testUnzipWithAppend(): void
    {
        $zipService = new ZipService();

        // Create a temporary zip file
        $zipFileName = $this->tempDir . '/test.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFileName, \ZipArchive::CREATE);
        $zip->addFromString('test1.txt', 'This is the first test file.');
        $zip->close();

        // Define the extraction path
        $extractionPath = $this->tempDir . '/extracted';

        // Extract the first zip file
        $zipService->unzip($zipFileName, $extractionPath, false);

        // Create another zip file to append
        $zipFileName2 = $this->tempDir . '/test2.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFileName2, \ZipArchive::CREATE);
        $zip->addFromString('test2.txt', 'This is the second test file.');
        $zip->close();

        // Call the unzip method with append
        $zipService->unzip($zipFileName2, $extractionPath, true);

        // Assert both files exist
        $this->assertFileExists($extractionPath . '/test1.txt');
        $this->assertStringEqualsFile($extractionPath . '/test1.txt', 'This is the first test file.');
        $this->assertFileExists($extractionPath . '/test2.txt');
        $this->assertStringEqualsFile($extractionPath . '/test2.txt', 'This is the second test file.');
    }

    public function testUnzipThrowsExceptionForInvalidZip(): void
    {
        $zipService = new ZipService();

        // Create an invalid zip file
        $invalidZipFileName = $this->tempDir . '/invalid.zip';
        file_put_contents($invalidZipFileName, 'This is not a valid zip file.');

        // Define the extraction path
        $extractionPath = $this->tempDir . '/extracted';

        // Expect an exception to be thrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open the zip file.');

        // Call the unzip method with the invalid zip file
        $zipService->unzip($invalidZipFileName, $extractionPath, false);
    }
}
