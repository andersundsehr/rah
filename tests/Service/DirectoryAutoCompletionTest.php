<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Generator;
use App\Service\DirectoryAutoCompletion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DirectoryAutoCompletionTest extends TestCase
{
    /**
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('provideTestCases')]
    public function __invoke(string $input, array $expected): void
    {
        $directoryAutoCompletion = new DirectoryAutoCompletion(dirname(__DIR__) . '/_fixture_/');
        self::assertEquals($expected, $directoryAutoCompletion($input));
    }

    public static function provideTestCases(): Generator
    {
        yield [
            'efnioebanfoebagbeaguiobeaubgeabgeoabgueia',
            [],
        ];
        yield [
            '',
            [
                'file',
                'templates/',
                'tests/',
            ],
        ];
        yield [
            't',
            [
                'templates/',
                'tests/',
            ],
        ];
        yield [
            'tes',
            [
                'tests/',
                'tests/A/',
                'tests/B/',
            ],
        ];
        yield [
            'tests',
            [
                'tests/',
                'tests/A/',
                'tests/B/',
            ],
        ];
        yield [
            'tests/',
            [
                'tests/A/',
                'tests/B/',
            ],
        ];
    }
}
