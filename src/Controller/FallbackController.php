<?php

namespace App\Controller;

use App\Dto\Deployment;
use App\Service\StorageUsageService;
use App\Service\ProjectService;
use App\Service\UrlService;
use App\UnderscoreHeader\HeadersFileService;
use GuzzleHttp\Psr7\Uri;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;

use function explode;
use function file_exists;
use function is_dir;
use function is_file;
use function pathinfo;
use function str_ends_with;
use function str_replace;
use function trim;

use const PATHINFO_EXTENSION;

final class FallbackController extends AbstractController
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly UrlService $urlService,
        private readonly StorageUsageService $diskUsageService,
        private readonly HeadersFileService $headersFileService,
    ) {
    }

    #[Route('/{catchall}', name: 'app_fallback', requirements: ['catchall' => '.+'], priority: -100)]
    public function show(Request $request): Response
    {
        $uri = ltrim($request->getPathInfo(), '/');
        $uri = str_replace('..', '', $uri);
        $uri = urldecode($uri);

        $object = $this->projectService->getProjectOrDeploymentFromHostname($request->getHost());

        $directory = '/app/public/';
        if ($object instanceof Deployment) {
            $directory = $object->path . '/';
        }

        $tryFiles = [
            $directory . $uri . '/index.html',
            $directory . $uri . '/index.htm',
            $directory . $uri,
            $directory . '/index.html',
            $directory . '/index.htm',
        ];
        if ($uri && is_dir($directory . $uri) && !str_ends_with($uri, '/')) {
            return $this->redirect($request->getSchemeAndHttpHost() . '/' . $uri . '/');
        }

        foreach ($tryFiles as $filename) {
            if (!file_exists($filename)) {
                continue;
            }

            if (is_dir($filename)) {
                return $this->listDirectory($filename, $directory);
            }

            if (is_file($filename)) {
                $binaryFileResponse = new BinaryFileResponse($filename);

                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                $contentType = MimeTypes::getDefault()->getMimeTypes($fileExtension)[0] ?? null;
                if ($contentType) {
                    $binaryFileResponse->headers->set('Content-Type', $contentType);
                }

                return $this->headersFileService->handleHeadersFile($directory, new Uri($request->getPathInfo()), $binaryFileResponse);
            }
        }

        return $this->json([
            'code' => 404,
            'message' => 'Not Found',
        ], 404);
    }

    private function listDirectory(string $filename, string $rootDir): Response
    {
        $directory = '/' . str_replace($rootDir, '', $filename);

        $breadcrumbs = [];
        $parts = explode('/', trim($directory, '/'));
        $path = '';
        foreach ($parts as $part) {
            $path .= '/' . $part;
            $breadcrumbs[] = [
                'uri' => $path,
                'name' => $part,
            ];
        }

        $finder = new Finder();
        $files = $finder->in($filename)->depth('0')->sortByName()->sortByType();

        $trim = trim($directory, '/');
        $baseUrl = '/' . ($trim ? ($trim . '/') : '');
        $listing = [];
        if ($directory !== '/') {
            $listing[] = [
                'uri' => $baseUrl . '..',
                'name' => '.. 👪',
                'type' => 'dir',
            ];
        }

        foreach ($files as $file) {
            $listing[] = [
                'uri' => $baseUrl . $file->getFilename() . ($file->isDir() ? '/' : ''),
                'name' => $file->getFilename() . ($file->isDir() ? '/' : ''),
                'type' => $file->isDir() ? 'dir' : 'file',
            ];
        }

        return $this->render('directory-listing.html.twig', [
            'directory' => $directory,
            'listing' => $listing,
            'breadcrumbs' => $breadcrumbs,
            'dashboardUrl' => $this->urlService->getUrl(),
            'diskUsage' => $this->diskUsageService->getDiskUsage(),
        ]);
    }
}
