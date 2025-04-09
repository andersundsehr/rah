<?php

namespace App\Command;

use App\Dto\Settings;
use App\Dto\Size;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ZipArchive;

use function basename;
use function filesize;
use function fopen;
use function is_file;
use function json_decode;
use function json_encode;
use function sprintf;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function tempnam;

use const JSON_PRETTY_PRINT;
use const PHP_EOL;

#[AsCommand(
    name: 'upload',
    description: 'upload files to a rah server (overwrites everything in the destination directory (not the complete deployment)',
    aliases: ['u'],
)]
class UploadCommand extends Command
{
    protected const ACTION = 'upload';
    private HttpClientInterface $client;
    private SymfonyStyle $io;

    public function __construct(private Filesystem $filesystem = new Filesystem())
    {
        $this->client = HttpClient::create();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::OPTIONAL, 'Directory to upload files from', './public')
            ->addArgument('destination', InputArgument::OPTIONAL, 'Destination to upload files to', '.');

        Settings::addOptionsToCommand($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $source = (string)$input->getArgument('source');
        $destination = (string)$input->getArgument('destination');
        $settings = Settings::fromEnv($input->getOptions());

        if (!str_starts_with($destination, '.')) {
            $destination = './' . ltrim($destination, '/');
        }

        $this->doUpload($source, $destination, $settings);

        $this->io->success('Done ' . static::ACTION . 'ing files!');

        return Command::SUCCESS;
    }

    protected function doUpload(string $source, string $destination, Settings $settings): void
    {
        $zipFileName = $this->filesystem->tempnam(sys_get_temp_dir(), 'rah_', '.zip');
        $zipFileName = basename($zipFileName); // TODO remove after testing

        try {
            $this->zipDirectory($source, $zipFileName);

            $this->uploadToServer($zipFileName, $destination, $settings);
        } finally {
            $this->filesystem->remove($zipFileName);
        }
    }

    private function zipDirectory(string $source, string $zipFileName): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create zip file');
        }
        $this->io->writeln('opened ZIP file: ' . $zipFileName, OutputInterface::VERBOSITY_VERY_VERBOSE);

        $source = str_replace('\\', '/', \Safe\realpath($source));

        if (is_dir($source)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST,
            );

            $alreadyAdded = [];
            foreach ($files as $file) {
                assert($file instanceof SplFileInfo);

                $filePath = $file->getRealPath() . ($file->isDir() ? '/' : '');
                if (isset($alreadyAdded[$filePath])) {
                    continue;
                }
                $alreadyAdded[$filePath] = true;
                $relativePath = substr($filePath, strlen($source) + 1);
                if (!$relativePath) {
                    $this->io->write('WARNING: Skipping empty path', true, OutputInterface::VERBOSITY_DEBUG);
                    continue;
                }

                $this->io->write('.', false, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $this->io->write(' ' . $relativePath, true, OutputInterface::VERBOSITY_DEBUG);

                if ($file->isDir()) {
                    $zip->addEmptyDir($relativePath);
                    continue;
                }
                $zip->addFile($filePath, $relativePath);
            }
            $this->io->write('', true, OutputInterface::VERBOSITY_VERY_VERBOSE);
        } elseif (is_file($source)) {
            $zip->addFile($source, basename($source));
        } else {
            throw new RuntimeException('Could not add file to zip');
        }

        $numFiles = $zip->numFiles;

        $zip->close();

        $this->io->success(sprintf('Zipped files successfully! files: %s size: %s', $numFiles, Size::formatSize(\Safe\filesize($zipFileName))));
    }

    private function uploadToServer(string $zipFileName, string $destination, Settings $settings): void
    {
        $parameters = (array)$settings;
        $parameters['destination'] = $destination;

        $url = $settings->api . '/api/deployment';
        $this->io->writeln('start upload to: ' . $url, OutputInterface::VERBOSITY_VERY_VERBOSE);
        $response = $this->client->request(static::ACTION === 'append' ? 'POST' : 'PUT', $url, [
            'query' => $parameters,
            'body' => \Safe\fopen($zipFileName, 'rb'),
            'headers' => [
                'Content-Type' => 'application/zip',
                'Content-Length' => (string)\Safe\filesize($zipFileName),
                'Accept' => 'application/json',
            ],
        ]);
        if ($response->getStatusCode() >= 300 || true) {
            $content = $response->getContent(false);
            echo str_replace('http://rah.localhost/', 'http://rah.localhost:3333/', $response->getInfo('url')) . PHP_EOL;
            echo \Safe\json_encode(\Safe\json_decode($content), JSON_PRETTY_PRINT) . PHP_EOL;

            echo 'size: ' . Size::formatSize(\Safe\filesize($zipFileName)) . PHP_EOL;

            throw new RuntimeException(sprintf("Upload failed (%s): %s %s", $response->getInfo('url'), $response->getStatusCode(), $content));
        }
        $location = $response->getHeaders()['location'] ?? '';
        $this->io->success('Uploaded successful! ' . $location);
    }
}
