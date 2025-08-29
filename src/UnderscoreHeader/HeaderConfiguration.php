<?php

declare(strict_types=1);

namespace App\UnderscoreHeader;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\Response;

use function Safe\preg_match;
use function in_array;
use function preg_quote;
use function str_contains;
use function strtolower;

final readonly class HeaderConfiguration
{
    public const array SKIPPED_RESPONSE_HEADERS = [
        'accept-ranges',
        'age',
        'allow',
        'alt-svc',
        'connection',
        'content-encoding',
        'content-length',
        'content-range',
        'date',
        'server',
        'trailer',
        'transfer-encoding',
        'upgrade',
    ];

    public function __construct(
        private string $matchRule,
        /**
         * @var array<string, string|false>
         */
        private array $headers,
    ) {
        if (str_contains($this->matchRule, ':')) {
            throw new InvalidArgumentException('Placeholders not supported do not use : in match rules');
        }
    }

    public function matches(UriInterface $requestUri): bool
    {
        $path = $requestUri->getPath();
        $matchRule = str_replace('\*', '.*', preg_quote($this->matchRule, '@'));
        return (bool)preg_match('@^' . $matchRule . '$@', $path);
    }

    /**
     * @template T of ResponseInterface|Response
     * @param T $response
     * @return T
     */
    public function apply(ResponseInterface|Response $response): ResponseInterface|Response
    {
        foreach ($this->headers as $name => $value) {
            if (in_array(strtolower($name), self::SKIPPED_RESPONSE_HEADERS, true)) {
                continue;
            }

            if ($value === false) {
                if ($response instanceof ResponseInterface) {
                    $response = $response->withoutHeader($name);
                    continue;
                }

                $response->headers->remove($name);
                continue;
            }

            if ($response instanceof ResponseInterface) {
                $response = $response->withHeader($name, $value);
                continue;
            }

            $response->headers->set($name, $value);
        }

        return $response;
    }
}
