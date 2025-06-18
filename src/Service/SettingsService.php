<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Settings;
use OndraM\CiDetector\CiDetector;
use RuntimeException;

use function getenv;
use function rtrim;
use function Safe\preg_replace;
use function strtolower;
use function trim;
use function Safe\shell_exec;

final readonly class SettingsService
{
    /**
     * @param array<string, string> $options
     */
    public function fromEnv(array $options): Settings
    {
        $api = $options['api'] ?: getenv('RAH_API') ?: throw new RuntimeException('env RAH_API is not set (required)');
        $api = rtrim((string) $api, '/');
        $api = rtrim($api, '/api');
        if (!str_starts_with($api, 'http')) {
            throw new RuntimeException('env RAH_API should start with http or https');
        }

        // if no CI detected use only options an env variables and set
        // if CI detected also use detected values as fallback

        $detected = [];

        $ciDetector = new CiDetector();
        if ($ciDetector->isCiDetected()) {
            $ci = $ciDetector->detect();
            $detected = [
                'CI' => $ci->getCiName(),
                'project' => $ci->getRepositoryName(),
                'deployment' => $ci->getBranch(),
                'message' => shell_exec('HOME= git log -1 --pretty=%B') ?: '',
                'defaultDeployment' => shell_exec("git remote show origin | grep 'HEAD branch' | awk '{print $3}'") ?: '',
            ];
        }

        $projectName = $this->required($options, $detected, 'project', 'RAH_PROJECTNAME');
        $deployment = $this->required($options, $detected, 'deployment', 'RAH_DEPLOYMENT');
        $deploymentMessage = $this->required($options, $detected, 'message', 'RAH_DEPLOYMENT_MESSAGE');
        $defaultDeployment = $this->required($options, $detected, 'defaultDeployment', 'RAH_DEFAULT_DEPLOYMENT');

        return new Settings(
            $api,
            $this->sanitiseForUri($projectName),
            $this->sanitiseForUri($deployment),
            $deploymentMessage,
            $this->sanitiseForUri($defaultDeployment),
        );
    }

    /**
     * @param array<string, string> $options
     * @param array<string, string> $detected
     */
    private function required(array $options, array $detected, string $name, string $envName): string
    {
        $errorMessage = 'required setting ' . $name . ' not found, please provide option --' . $name . ' or set env variable ' . $envName;
        if (!$detected) {
            $errorMessage .= ' CI not detected, so no fallback from CI';
        }

        if (isset($detected['CI'])) {
            $errorMessage .= ' CI "' . $detected['CI'] . '" detected, but no fallback for ' . $name . ' found';
        }

        return $this->optional($options, $detected, $name, $envName) ?: throw new RuntimeException($errorMessage);
    }

    /**
     * @param array<string, string> $options
     * @param array<string, string> $detected
     */
    private function optional(array $options, array $detected, string $name, string $envName): string
    {
        return ($options[$name] ?? '')
            ?: getenv($envName)
            ?: ($detected[$name] ?? '');
    }

    private function sanitiseForUri(string $string): string
    {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9-]+/', '-', $string);
        return trim($string, '-');
    }
}
