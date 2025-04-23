<?php

namespace App\Controller;

use App\Dto\Settings;
use App\Service\DeploymentService;
use App\Service\ProjectService;
use App\Service\ZipService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

use function fclose;
use function ltrim;
use function str_replace;
use function stream_copy_to_stream;
use function sys_get_temp_dir;
use function tempnam;

final class ApiDeploymentController extends AbstractController
{
    public function __construct(
        private readonly ZipService $zipService,
        private readonly Filesystem $filesystem,
        private readonly ProjectService $projectService,
    ) {
    }

    #[Route('/api/deployment', name: 'app_api_deployment_delete', methods: ['DELETE'])]
    #[Route('/api/deployment:delete', name: 'app_api_deployment_delete_get', methods: ['GET'])]
    public function delete(
        #[MapQueryString] Settings $settings
    ): JsonResponse {
        try {
            $deployment = $this->projectService->loadDeploymentFromSettings($settings);
        } catch (NotFoundHttpException) {
            return $this->json([
                'status' => 'ok',
                'message' => 'already deleted',
            ], 200);
        }

        $this->filesystem->remove($deployment->path);

        return $this->json([
            'status' => 'ok',
            'message' => 'deleted',
        ], 200);
    }

    #[Route('/api/deployment', name: 'app_api_upload', methods: ['POST', 'PUT', 'GET'])]
    public function upload(
        Request $request,
        #[MapQueryString] Settings $settings,
        #[MapQueryParameter] string $destination,
    ): JsonResponse {
        $deployment = $this->projectService->createDeploymentFromSettings($settings);

        $append = $request->getMethod() === Request::METHOD_POST;
        if ($request->getMethod() === 'GET') {
            return $this->json([
                'status' => 'ok',
                'destination' => $destination,
                'append' => $append,
                'method' => $request->getMethod(),
                'settings' => $settings,
            ]);
        }

        // make path safe and relative
        $destination = str_replace('..', '', $destination);
        $destination = ltrim($destination, './');

        $zipFileName = $this->filesystem->tempnam(sys_get_temp_dir(), 'rah-', '.zip');

        try {
            // write $zipFile stream to file named $zipFileName
            $this->filesystem->mkdir(\dirname($zipFileName));

            $input = fopen('php://input', 'rb');
            $zipFile = fopen($zipFileName, 'wb+');
            stream_copy_to_stream($input, $zipFile);
            fclose($input);
            fclose($zipFile);

            $this->zipService->unzip($zipFileName, $deployment->path . '/' . $destination, $append);
        } finally {
            $this->filesystem->remove($zipFileName);
        }

        $deployment = $deployment->reload();

        return $this->json([
            'status' => 'ok',
            'deploymentSize' => $deployment->size,
            'settings' => $settings,
            'Location' => $deployment->url,
        ], 201, [
            'Location' => $deployment->url,
        ]);
    }
}
