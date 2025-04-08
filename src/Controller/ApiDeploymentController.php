<?php

namespace App\Controller;

use App\Dto\Deployment;
use App\Dto\Settings;
use App\Service\ZipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

use function filesize;
use function is_file;
use function ltrim;
use function str_replace;
use function str_starts_with;
use function stream_copy_to_stream;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class ApiDeploymentController extends AbstractController
{
    public function __construct(private readonly ZipService $zipService) {}

    #[Route('/api/deployment', name: 'app_api_deployment_delete', methods: ['DELETE'])]
    #[Route('/api/deployment:delete', name: 'app_api_deployment_delete_get', methods: ['GET'])]
    public function delete(
        Request $request,
        #[MapQueryString] Settings $settings
    ): JsonResponse
    {
        $deployment = Deployment::findSettings($request, $settings);
        dd($deployment); // TODO delete deployment

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
        $deployment = Deployment::createFromSettings($request, $settings);
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

        $uploadBody = $request->getContent(true);

        // make path safe and relative
        $destination = str_replace('..', '', $destination);
        $destination = ltrim($destination, './');

        $zipFileName = tempnam(sys_get_temp_dir(), 'rah-') . '.zip';


        try {
            // write $zipFile stream to file named $zipFileName
            stream_copy_to_stream($uploadBody, fopen($zipFileName, 'wb'));

            $this->zipService->unzip($zipFileName, $deployment->path . '/' . $destination, $append);
        } finally {
            if (is_file($zipFileName)) {
                unlink($zipFileName);
            }
        }

        $deployment = Deployment::findSettings($request, $settings); // update size stats

        return $this->json([
            'status' => 'ok',
            'deploymentSize' => $deployment->size,
            'settings' => $settings,
            'Location' => Deployment::getUrl($request, $settings->projectName . '--' . $settings->deployment),
        ], 201, [
            'Location' => Deployment::getUrl($request, $settings->projectName . '--' . $settings->deployment),
        ]);
    }


}
