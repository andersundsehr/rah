<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ProjectService;
use App\Service\ZipService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ZipServiceTest extends RahKernelTestcase
{
    private ZipService $zipService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zipService = self::getContainer()->get(ZipService::class);
    }

    public function testUnzip(): void
    {
        // Create a temporary zip file
        $zipFileName = $this->tempStorage . '/test.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFileName, \ZipArchive::CREATE);
        $zip->addFromString('test.txt', 'This is a test file.');
        $zip->close();

        // Define the extraction path
        $extractionPath = $this->tempStorage . '/extracted';

        // Call the unzip method
        $this->zipService->unzip($zipFileName, $extractionPath, false);

        // Assert the file was extracted
        $this->assertDirectoryExists($extractionPath);
        $this->assertFileExists($extractionPath . '/test.txt');
        $this->assertStringEqualsFile($extractionPath . '/test.txt', 'This is a test file.');
    }

    public function testUnzipWithAppend(): void
    {
        // Create a temporary zip file
        $zipFileName = $this->tempStorage . '/test.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFileName, \ZipArchive::CREATE);
        $zip->addFromString('test1.txt', 'This is the first test file.');
        $zip->close();

        // Define the extraction path
        $extractionPath = $this->tempStorage . '/extracted';

        // Extract the first zip file
        $this->zipService->unzip($zipFileName, $extractionPath, false);

        // Create another zip file to append
        $zipFileName2 = $this->tempStorage . '/test2.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFileName2, \ZipArchive::CREATE);
        $zip->addFromString('test2.txt', 'This is the second test file.');
        $zip->close();

        // Call the unzip method with append
        $this->zipService->unzip($zipFileName2, $extractionPath, true);

        // Assert both files exist
        $this->assertFileExists($extractionPath . '/test1.txt');
        $this->assertStringEqualsFile($extractionPath . '/test1.txt', 'This is the first test file.');
        $this->assertFileExists($extractionPath . '/test2.txt');
        $this->assertStringEqualsFile($extractionPath . '/test2.txt', 'This is the second test file.');
    }

    public function testUnzipThrowsExceptionForInvalidZip(): void
    {
        // Create an invalid zip file
        $invalidZipFileName = $this->tempStorage . '/invalid.zip';
        $this->filesystem->dumpFile($invalidZipFileName, 'This is not a valid zip file.');

        // Define the extraction path
        $extractionPath = $this->tempStorage . '/extracted';

        // Expect an exception to be thrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open the zip file.');

        // Call the unzip method with the invalid zip file
        $this->zipService->unzip($invalidZipFileName, $extractionPath, false);
    }
}
