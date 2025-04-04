<?php

namespace App\Controller;

use App\Dto\Deployment;
use App\Dto\Settings;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ApiDeleteController extends AbstractController
{
    #[Route('/api/deployment', name: 'app_api_delete', methods: ['DELETE'])]
    #[Route('/api/deployment/delete', name: 'app_api_delete_get', methods: ['GET'])]
    public function delete(
        Request $request,
        #[MapQueryString] Settings $settings
    ): JsonResponse
    {
        $deployment = Deployment::fromSettings($request, $settings);
        dd($deployment);

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

        $zipFile = $request->getContent();

        return $this->json([
            'status' => 'ok',
            'destination' => $destination,
            'append' => $append,
            'settings' => $settings,
            'bodySize' => strlen($zipFile),
            'Location' => Deployment::getUrl($request, $settings->projectName . '--' . $settings->deployment),
        ], 201, [
            'Location' => Deployment::getUrl($request, $settings->projectName . '--' . $settings->deployment),
        ]);
    }
}
