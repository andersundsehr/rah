<?php

namespace App\Command;

use App\Dto\Settings;
use App\Dto\Size;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Safe\Exceptions\JsonException;
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
use function getenv;
use function is_file;
use function Safe\filesize;
use function Safe\fopen;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\realpath;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function sys_get_temp_dir;

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

    private readonly HttpClientInterface $client;

    private SymfonyStyle $io;

    private string $apiKey;

    public function __construct(private readonly Filesystem $filesystem = new Filesystem())
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

        $this->apiKey = getenv('RAH_API_KEY') ?: '';
        if (!str_starts_with($this->apiKey, 'rah_')) {
            $this->io->error('env RAH_API_KEY must be set and start with rah_');
            return Command::FAILURE;
        }

        $source = (string)$input->getArgument('source');
        $destination = (string)$input->getArgument('destination');
        $settings = Settings::fromEnv($input->getOptions());

        if (!str_starts_with($destination, '.')) {
            $destination = './' . ltrim($destination, '/');
        }

        $this->doUpload($source, $destination, $settings);

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

        $source = str_replace('\\', '/', realpath($source));

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

        $this->io->success(sprintf('Zipped files successfully! files: %s size: %s', $numFiles, Size::formatSize(filesize($zipFileName))));
    }

    private function uploadToServer(string $zipFileName, string $destination, Settings $settings): void
    {
        $parameters = (array)$settings;
        $parameters['destination'] = $destination;

        $url = $settings->api . '/api/deployment';
        $this->io->writeln('start upload to: ' . $url, OutputInterface::VERBOSITY_VERY_VERBOSE);
        $response = $this->client->request(static::ACTION === 'append' ? 'POST' : 'PUT', $url, [
            'query' => $parameters,
            'body' => fopen($zipFileName, 'rb'),
            'auth_basic' => ['api', $this->apiKey],
            'headers' => [
                'Content-Type' => 'application/zip',
                'Content-Length' => (string)filesize($zipFileName),
                'Accept' => 'application/json',
                'User-Agent' => 'rah/' . ($this->getApplication()?->getVersion() ?? throw new RuntimeException('no version')),
            ],
        ]);
        if ($response->getStatusCode() === 401) {
            $this->io->error(
                'Authentication failed. Please check your API key and try again. RAH_API_TOKEN' . PHP_EOL . 'Response 401: ' . $response->getContent(false),
            );
            return;
        }

        if ($response->getStatusCode() >= 300 || true) {
            $content = $response->getContent(false);
            echo str_replace('http://rah.localhost/', 'http://rah.localhost:3333/', $response->getInfo('url')) . PHP_EOL; // TODO remove after testing
            try {
                echo json_encode(json_decode($content), JSON_PRETTY_PRINT) . PHP_EOL;
            } catch (JsonException) {
                echo $content . PHP_EOL;
            }

            throw new RuntimeException(sprintf("Upload failed (%s): %s %s", $response->getInfo('url'), $response->getStatusCode(), $content));
        }

        $location = $response->getHeaders()['location'] ?? '';
        $this->io->success('Done ' . static::ACTION . 'ing files!' . ($location ? ' see: ' . $location : ''));
    }
}
