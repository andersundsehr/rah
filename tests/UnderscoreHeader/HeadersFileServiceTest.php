<?php

declare(strict_types=1);

namespace App\Tests\UnderscoreHeader;

use Generator;
use App\UnderscoreHeader\HeaderConfiguration;
use App\UnderscoreHeader\HeadersFileService;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Filesystem;

class HeadersFileServiceTest extends TestCase
{
    #[Test]
    #[DataProvider('provideParseCasesExceptions')]
    public function parseExceptions(string $content, string $message): void
    {
        $service = new HeadersFileService(new Filesystem());
        $this->expectExceptionMessage($message);

        $service->parse($content);
    }

    public static function provideParseCasesExceptions(): Generator
    {
        yield [
            <<<'EOF'
                /invalid:rule
                EOF,
            'Placeholders not supported do not use : in match rules, in line 1',
        ];
        yield [
            <<<'EOF'
                /valid
                    /invalid header
                EOF,
            "Header Name '/invalid header' contains invalid characters, in line 2",
        ];
        yield [
            <<<'EOF'
                /valid
                    Invalid-Header-Name-!
                EOF,
            "Header Name 'Invalid-Header-Name-!' contains invalid characters, in line 2",
        ];
        yield [
            <<<'EOF'
                /valid
                Valid-Header: Value
                EOF,
            'Header lines must be indented, in line 2',
        ];
        yield [
            <<<'EOF'
                /valid
                /other
                EOF,
            'rule without headers, in line 1',
        ];
        yield [
            <<<'EOF'
                /valid
                    H: a
                /other
                EOF,
            'rule without headers, in line 3',
        ];
    }

    #[Test]
    #[DataProvider('provideParseCases')]
    public function parse(string $content, HeaderConfiguration ...$expectedResults): void
    {
        $service = new HeadersFileService(new Filesystem());
        $actualResults = $service->parse($content);
        $this->assertEquals($expectedResults, $actualResults);
    }

    public static function provideParseCases(): Generator
    {
        yield [
            <<<'EOF'
                /*
                    X-Frame-Options:     DENY
                    X-Content-Type-Options: nosniff
                # comment top level
                /other
                    X-Frame-Options: SAMEORIGIN
                    #Other: comment
                EOF,
            new HeaderConfiguration('/*', [
                'X-Frame-Options' => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
            ]),
            new HeaderConfiguration('/other', [
                'X-Frame-Options' => 'SAMEORIGIN',
            ]),
        ];
        yield [
            <<<'EOF'
                /*
                  X-Frame-Options:     DENY
                EOF,
            new HeaderConfiguration('/*', [
                'X-Frame-Options' => 'DENY',
            ]),
        ];
        yield [
            <<<'EOF'
                /*
                  Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://example.com; style-src 'self' 'unsafe-inline' https://example.com; img-src 'self' data: https://example.com; font-src 'self' https://example.com; connect-src 'self' https://example.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self';
                EOF,
            new HeaderConfiguration('/*', [
                'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://example.com; style-src 'self' 'unsafe-inline' https://example.com; img-src 'self' data: https://example.com; font-src 'self' https://example.com; connect-src 'self' https://example.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self';",
            ]),
        ];
        yield [
            '',
        ];
    }

    /**
     * @param list<HeaderConfiguration> $configurations
     */
    #[Test]
    #[DataProvider('provideMatchConfigurations')]
    public function matchConfigurations(
        array $configurations,
        UriInterface $requestUri,
        ResponseInterface $response,
        ResponseInterface $expected
    ): void {
        $service = new HeadersFileService(new Filesystem());
        $actual = $service->matchConfigurations($configurations, $requestUri, $response);
        $this->assertEquals($expected, $actual);
    }

    public static function provideMatchConfigurations(): Generator
    {
        yield 'default' => [
            'configurations' => [
                new HeaderConfiguration('/*', [
                    'X-Frame-Options' => 'DENY',
                    'X-Content-Type-Options' => 'nosniff',
                ]),
            ],
            'requestUri' => new Uri('/any/path'),
            'response' => new Response(),
            'expected' => new Response()->withHeader('X-Frame-Options', 'DENY')->withHeader('X-Content-Type-Options', 'nosniff'),
        ];
        yield 'no match' => [
            'configurations' => [
                new HeaderConfiguration('/other', [
                    'X-Frame-Options' => 'DENY',
                ]),
            ],
            'requestUri' => new Uri('/any/path'),
            'response' => new Response(),
            'expected' => new Response(),
        ];
        yield 'remove header' => [
            'configurations' => [
                new HeaderConfiguration('/*', [
                    'X-Frame-Options' => false,
                ]),
            ],
            'requestUri' => new Uri('/any/path'),
            'response' => new Response()->withHeader('X-Frame-Options', 'DENY')->withHeader('X-Content-Type-Options', 'nosniff'),
            'expected' => new Response()->withHeader('X-Content-Type-Options', 'nosniff'),
        ];
        yield 'skipped header' => [
            'configurations' => [
                new HeaderConfiguration('/*', [
                    'Content-Length' => '123456',
                ]),
            ],
            'requestUri' => new Uri('/any/path'),
            'response' => new Response(),
            'expected' => new Response(),
        ];
    }
}
