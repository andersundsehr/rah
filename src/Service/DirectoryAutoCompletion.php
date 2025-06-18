<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Completion\CompletionInput;

use function count;
use function Safe\glob;
use function is_dir;
use function strlen;
use function substr;
use function Safe\getcwd;

/**
 * usage:
 * ->addArgument('source', InputArgument::OPTIONAL, 'Directory to upload files from', './public', (new DirectoryAutoCompletion())(...))
 */
final readonly class DirectoryAutoCompletion
{
    private string $cwd;

    public function __construct(?string $cwd = null)
    {
        $this->cwd = rtrim($cwd ?? getcwd(), '/') . '/';
    }

    /**
     * @return list<string>
     */
    public function __invoke(CompletionInput|string $input): array
    {
        $currentValue = $input instanceof CompletionInput ? $input->getCompletionValue() : $input;

        $suggestedDirectories = [];
        $folders = glob($this->cwd . $currentValue . '*');
        foreach ($folders as $file) {
            if ($file === '.') {
                continue;
            }

            if ($file === '..') {
                continue;
            }

            if (!str_starts_with((string) $file, $this->cwd . $currentValue)) {
                continue;
            }

            if (is_dir($file)) {
                $file .= '/';
            }

            // remove first part of the path even if cwd is in the string multiple times
            $file = substr((string) $file, strlen($this->cwd));
            $suggestedDirectories[] = $file;
        }

        if (count($suggestedDirectories) === 1) {
            return [$suggestedDirectories[0], ...$this->__invoke($suggestedDirectories[0])];
        }

        return $suggestedDirectories;
    }
}
