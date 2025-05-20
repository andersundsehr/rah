<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Generator;
use App\Dto\NamedObject;
use App\Service\NameShortingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NameShortingServiceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideProjectAndDeploymentNames')]
    public function createShortName(
        string $project,
        string $deployment,
        string $expectedShortName,
    ): void {

        $nameShortingService = new NameShortingService();
        $shortName = $nameShortingService->createShortName($project, $deployment);
        $this->assertSame($expectedShortName, $shortName);
        $this->assertLessThanOrEqual(63, strlen($shortName), 'DNS max label length is 63 characters given: ' . $shortName);
        $this->assertSame(2, count(explode('--', $shortName)), 'Short name must contain exactly one -- given: ' . $shortName);
    }

    public static function provideProjectAndDeploymentNames(): Generator
    {
        yield 'short' => [
            'project' => 'messe-dachs-lms-components',
            'deployment' => 'main',
            'expectedShortName' => 'messe-dachs-lms-components--main',
        ];
        yield 'long-project' => [
            'project' => 'messe-dachs-lms-components-123456-help-me-really-long',
            'deployment' => 'lms117sus-3295-translatable',
            'expectedShortName' => 'messe-dachs-lms-components-1-768e4--lms117sus-3295-translatable',
        ];
        yield 'long-project same hash' => [
            'project' => 'messe-dachs-lms-components-123456-help-me-really-long',
            'deployment' => 'lms117sus-3295-translatable-a',
            'expectedShortName' => 'messe-dachs-lms-components-768e4--lms117sus-3295-translatable-a',
        ];
        yield 'long-project with - at wrong position' => [
            'project' => 'messe-dachs-lms-components1-23456-help-me-really-long',
            'deployment' => 'lms117sus-3295-translatable',
            'expectedShortName' => 'messe-dachs-lms-components1-32177--lms117sus-3295-translatable',
        ];
        yield 'long-deployment' => [
            'project' => 'messe-dachs-lms-components',
            'deployment' => 'task/lms117sus-3280-accessable-components-lmsdatetimepicker',
            'expectedShortName' => 'messe-dachs-lms-components--task/lms117sus-3280-accessabl-405c0',
        ];
        yield 'both-long' => [
            'project' => 'andersundsehr-project-123456-help-me-really-long',
            'deployment' => 'task/lms117sus-3280-accessable-components-lmsdatetimepicker',
            'expectedShortName' => 'andersundsehr-project-12-f972f--task/lms117sus-3280-acces-405c0',
        ];
    }

    /**
     * @param list<NamedObject> $objects
     */
    #[DataProvider('findObjectByNameDataProvider')]
    public function testFindObjectByName(string $string, array $objects, ?NamedObject $expected): void
    {
        $nameShortingService = new NameShortingService();
        $object = $nameShortingService->findObjectByName($string, ...$objects);
        $this->assertSame($expected, $object);
    }

    public static function findObjectByNameDataProvider(): Generator
    {
        $myProject = new NamedObject('myproject');
        yield 'not found' => [
            'myproject',
            [],
            null,
        ];
        yield 'found' => [
            'myproject',
            [
                $myProject,
            ],
            $myProject,
        ];
        yield 'found the right one' => [
            'myproject',
            [
                new NamedObject('otherproject'),
                $myProject,
            ],
            $myProject,
        ];
        yield 'found with hash' => [
            'my-ced36',
            [
                new NamedObject('otherproject'),
                $myProject,
            ],
            $myProject,
        ];
        yield 'not found with correct hash, but wrong start' => [
            'not-my-ced36',
            [
                new NamedObject('otherproject'),
                $myProject,
            ],
            null,
        ];
    }
}
