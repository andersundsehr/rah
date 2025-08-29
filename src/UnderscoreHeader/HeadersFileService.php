<?php

declare(strict_types=1);

namespace App\UnderscoreHeader;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

use function Safe\preg_split;
use function Safe\preg_match;

final readonly class HeadersFileService
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    /**
     * @template T of ResponseInterface|Response
     * @param T $response
     * @return T
     */
    public function handleHeadersFile(string $htdocsRoot, string $requestPath, ResponseInterface|Response $response): ResponseInterface|Response
    {
        if (!$this->filesystem->exists($htdocsRoot . '/_headers')) {
            return $response;
        }

        $configurations = $this->parse($this->filesystem->readFile($htdocsRoot . '/_headers'));

        return $this->matchConfigurations($configurations, $requestPath, $response);
    }

    /**
     * @return list<HeaderConfiguration>
     */
    public function parse(string $fileContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $fileContent);

        $configurations = [];
        $currentMatchRule = null;
        $currentHeaders = [];
        $hasHeaderForCurrentRule = false;
        foreach ($lines as $i => $line) {
            assert(is_string($line));
            $lineNumber = $i + 1;
            $trimmedLine = trim($line);
            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue; // Skip empty lines and comments
            }

            if (preg_match('/^\S.*$/', $line)) {
                // New match rule or possibly a header line not indented
                $potentialMatchRule = trim($line);
                // If it looks like a match rule but contains a colon, throw match rule error
                if (preg_match('#^/.*:.*$#', $potentialMatchRule)) {
                    throw new InvalidArgumentException('Placeholders not supported do not use : in match rules, in line ' . $lineNumber);
                }

                // If it looks like a header (has colon and matches header pattern), throw header indentation error
                if (preg_match('/^[^\s:]+:.*$/', $potentialMatchRule)) {
                    throw new InvalidArgumentException('Header lines must be indented, in line ' . $lineNumber);
                }

                if ($currentMatchRule !== null) {
                    if (!$hasHeaderForCurrentRule) {
                        throw new InvalidArgumentException('rule without headers, in line ' . ($i /* previous rule's line */));
                    }

                    $configurations[] = new HeaderConfiguration($currentMatchRule, $currentHeaders);
                }

                $currentMatchRule = $potentialMatchRule;
                $currentHeaders = [];
                $hasHeaderForCurrentRule = false;
                $currentRuleLine = $lineNumber;
                continue;
            }

            // Header line: must be indented
            if (!preg_match('/^\s+/', $line)) {
                throw new InvalidArgumentException('Header lines must be indented, in line ' . $lineNumber);
            }

            if (preg_match('/^\s+(\S+):\s*(.*)$/', $line, $matches)) {
                if ($currentMatchRule === null) {
                    continue; // Skip headers without a match rule
                }

                $headerName = $matches[1];
                // Validate header name: only allow letters, digits, and hyphens
                if (!preg_match('/^[A-Za-z0-9-]+$/', $headerName)) {
                    throw new InvalidArgumentException(sprintf("Header Name '%s' contains invalid characters, in line ", $headerName) . $lineNumber);
                }

                $headerValue = $matches[2] === '' ? false : $matches[2];
                $currentHeaders[$headerName] = $headerValue;
                $hasHeaderForCurrentRule = true;
            } elseif (preg_match('/^\s+(\S.*)$/', $line, $matches)) {
                // Indented but not a valid header line
                $headerName = $matches[1];
                throw new InvalidArgumentException(sprintf("Header Name '%s' contains invalid characters, in line ", $headerName) . $lineNumber);
            }
        }

        // Add the last configuration if exists
        if ($currentMatchRule !== null) {
            if (!$hasHeaderForCurrentRule) {
                throw new InvalidArgumentException('rule without headers, in line ' . ($currentRuleLine ?? 1));
            }

            $configurations[] = new HeaderConfiguration($currentMatchRule, $currentHeaders);
        }

        return $configurations;
    }

    /**
     * @param list<HeaderConfiguration> $configurations
     * @template T of ResponseInterface|Response
     * @param T $response
     * @return T
     */
    public function matchConfigurations(array $configurations, string $requestPath, ResponseInterface|Response $response): ResponseInterface|Response
    {
        foreach ($configurations as $configuration) {
            if ($configuration->matches($requestPath)) {
                return $configuration->apply($response);
            }
        }

        return $response;
    }
}
