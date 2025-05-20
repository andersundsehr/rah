<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Deployment;
use App\Dto\NamedObject;
use App\Dto\Project;

use function hash;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;

final readonly class NameShortingService
{
    const int MAX_LABEL_LENGTH = 63;

    const int MAX_PROJECT_LENGTH = 30;

    /**
     * goals:
     * - max length of 63 characters
     * - shorten as little as possible
     * - keep the project and deployment names readable
     * - keep the project and deployment names unique (done with hash)
     * - the deployment name must be longer than the project name as it is more relevant for the human
     */
    public function createShortName(string $project, string $deployment): string
    {
        $projectLength = strlen($project);
        $deploymentLength = strlen($deployment);

        while ($projectLength + 2 + $deploymentLength > self::MAX_LABEL_LENGTH) {
            if ($projectLength > self::MAX_PROJECT_LENGTH) {
                $projectLength--;
            } elseif ($projectLength > $deploymentLength) {
                $projectLength--;
            } else {
                $deploymentLength--;
            }
        }

        return $this->hashIfTooLong($project, $projectLength) . '--' . $this->hashIfTooLong($deployment, $deploymentLength);
    }

    public function hashIfTooLong(string $string, int $cutToLength): string
    {
        $strlen = strlen($string);
        if ($strlen <= $cutToLength) {
            return $string;
        }

        $substr = substr($string, 0, $cutToLength - 6);
        if (str_ends_with($substr, '-')) {
            $substr = substr($substr, 0, -1);
        }

        return $substr . '-' . $this->createHash($string);
    }

    private function createHash(string $string): string
    {
        return substr(hash('sha512', $string), 0, 5);
    }

    /**
     * @template T of Project|Deployment|NamedObject
     * @param T ...$objects
     * @return T|null
     */
    public function findObjectByName(string $string, Project|Deployment|NamedObject ...$objects): Project|Deployment|NamedObject|null
    {
        foreach ($objects as $object) {
            if ($object->name === $string) {
                return $object;
            }

            $hash = $this->createHash($object->name);
            if (!str_ends_with($string, '-' . $hash)) {
                continue;
            }

            $objectName = substr($string, 0, -strlen('-' . $hash));
            if (!str_starts_with($object->name, $objectName)) {
                continue;
            }

            return $object;
        }

        return null;
    }
}
