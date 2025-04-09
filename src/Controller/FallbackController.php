<?php

namespace App\Controller;

use App\Service\ProjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Attribute\Route;

use function dirname;
use function file_exists;
use function is_dir;
use function is_file;
use function pathinfo;
use function str_ends_with;
use function str_replace;

use const PATHINFO_EXTENSION;

final class FallbackController extends AbstractController
{
    public function __construct(private readonly ProjectService $projectService)
    {
    }

    #[Route('/{catchall}', name: 'app_fallback', requirements: ['catchall' => '.+'], priority: -100)]
    public function show(Request $request): Response
    {
        $uri = ltrim($request->getPathInfo(), '/');
        $uri = str_replace('..', '', $uri);
        $uri = urldecode($uri);

        [$projectName, $deploymentName] = $this->projectService->getProjectParts($request->getHost());

        $directory = '/app/public/';
        if ($projectName && $deploymentName) {
            $directory = '/storage/' . $projectName . '/' . $deploymentName . '/';
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
                return $this->listDirectory($filename, $request, $uri, $directory);
            }

            if (is_file($filename)) {
                $binaryFileResponse = new BinaryFileResponse($filename);

                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                $contentType = MimeTypes::getDefault()->getMimeTypes($fileExtension)[0] ?? null;
                if ($contentType) {
                    $binaryFileResponse->headers->set('Content-Type', $contentType);
                }

                return $binaryFileResponse;
            }
        }

        return $this->json([
            'code' => 404,
            'message' => 'Not Found',
        ], 404);
    }

    private function listDirectory(string $filename, Request $request, string $uri, string $directory): Response
    {
        $finder = new Finder();
        $files = $finder->in($filename)->depth('0')->sortByName()->sortByType();

        $css = <<<CSS
:root {
  color-scheme: light dark;
  padding: 20px;
}

CSS;

        $html = '<!DOCTYPE html>';
        $html .= '<html><body>';
        $html .= '<style>' . $css . '</style>';
        $html .= '<h1>Index of ' . str_replace($directory, '', $filename) . '</h1>';
        $html .= '<ul>';
        $dirname = dirname($uri);
        if ($dirname) {
            $html .= '<li style="padding: 5px;"><a href="' . $request->getSchemeAndHttpHost() . '/' . $dirname . '/">' . ($dirname === '.' ? '. &lt;parent>' : $dirname) . '</a></li>';
        }

        foreach ($files as $file) {
            $uriPath = $uri . $file->getBasename();
            $html .= '<li style="padding: 5px;"><a href="' . $request->getSchemeAndHttpHost() . '/' . $uriPath . '">' . $uriPath . ($file->isDir() ? '/' : '') . '</a></li>';
        }

        $html .= '</ul>';
        $html .= '</body></html>';
        return new Response($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }
}
