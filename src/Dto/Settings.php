<?php

declare(strict_types=1);

namespace App\Dto;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Validator\Constraints as Assert;

use function strtolower;

final readonly class Settings
{

    public const DEFAULT_DELETE_AFTER = '1m';

    public function __construct(
        public string $api,

        #[Assert\NotBlank(message: 'parameter projectName should not be empty')]
        #[Assert\Regex(
            pattern: '/^[a-z0-9-]+$/',
            message: 'parameter projectName should only contain lowercase letters, numbers and dashes',
        )]
        public string $projectName,

        #[Assert\NotBlank(message: 'parameter deployment should not be empty')]
        #[Assert\Regex(
            pattern: '/^[a-z0-9-]+$/',
            message: 'parameter deployment should only contain lowercase letters, numbers and dashes',
        )]
        public string $deployment,

        #[Assert\NotBlank(message: 'parameter deploymentMessage should not be empty')]
        public string $deploymentMessage,

        #[Assert\NotBlank(message: 'parameter defaultDeployment should not be empty')]
        #[Assert\Regex(
            pattern: '/^[a-z0-9][a-z0-9-]*$/',
            message: 'parameter defaultDeployment should only contain lowercase letters, numbers and dashes',
        )]
        public string $defaultDeployment,

        #[Assert\NotBlank(message: 'parameter deleteAfter should not be empty')]
        #[Assert\Regex(
            pattern: '/^[0-9+][dwmy]$/',
            message: 'parameter deleteAfter should be a number followed by a unit (d,w,m,y)',
        )]
        public string $deleteAfter,

        public string $deleteIfMissingBranch,
    ) {}

    /**
     * @param array<string, string> $options
     */
    public static function fromEnv(array $options): self
    {
        $api = $options['api'] ?: getenv('RAH_API') ?: throw new RuntimeException('env RAH_API is not set (required)');
        $api = rtrim($api, '/');
        $api = rtrim($api, '/api');
        if (!str_starts_with($api, 'http')) {
            throw new RuntimeException('env RAH_API should start with http or https');
        }
        return new self(
            $api,
            self::sanitiseForUri($options['project'] ?: getenv('RAH_PROJECTNAME') ?: getenv('CI_PROJECT_PATH_SLUG') ?: throw new RuntimeException('env RAH_PROJECTNAME or CI_PROJECT_PATH_SLUG is not set (required)')),
            self::sanitiseForUri($options['deployment'] ?: getenv('RAH_DEPLOYMENT') ?: getenv('CI_COMMIT_REF_SLUG') ?: throw new RuntimeException('env RAH_DEPLOYMENT or CI_COMMIT_REF_SLUG is not set (required)')),
            $options['message'] ?: getenv('RAH_DEPLOYMENT_MESSAGE') ?: getenv('CI_COMMIT_MESSAGE') ?: throw new RuntimeException(
                'env RAH_DEPLOYMENT_MESSAGE or CI_COMMIT_MESSAGE is not set (required)',
            ),
            self::sanitiseForUri($options['defaultDeployment'] ?: getenv('RAH_DEFAULT_DEPLOYMENT') ?: getenv('CI_DEFAULT_BRANCH') ?: throw new RuntimeException(
                'env RAH_DEFAULT_DEPLOYMENT or CI_DEFAULT_BRANCH is not set (required)',
            )),
            $options['deleteAfter'] ?: getenv('RAH_DELETE_AFTER') ?: getenv('RAH_DELETE_AFTER') ?: self::DEFAULT_DELETE_AFTER,
            $options['deleteIfMissingBranch'] ?: getenv('RAH_DELETE_IF_MISSING_BRANCH') ?: getenv('CI_COMMIT_BRANCH') ?: '',
        );
    }

    public static function sanitiseForUri(string $string): string
    {
        $string = strtolower($string);
        $string = \Safe\preg_replace('/[^a-z0-9-]+/', '-', $string);
        return trim($string, '-');
    }

    public static function addOptionsToCommand(Command $command): void
    {
        $command
            ->addOption('api', null, InputOption::VALUE_REQUIRED, 'rah server api (with http(s))')
            ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'project name')
            ->addOption('deployment', 'd', InputOption::VALUE_REQUIRED, 'deployment name')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, '(commit) message of the deployment')
            ->addOption('defaultDeployment', null, InputOption::VALUE_REQUIRED, 'default deployment name')
            ->addOption('deleteAfter', 'a', InputOption::VALUE_REQUIRED, 'delete after time (1m, 1h, 1d, 1w) (default: ' . Settings::DEFAULT_DELETE_AFTER . ')')
            ->addOption('deleteIfMissingBranch', 'b', InputOption::VALUE_REQUIRED, 'delete if missing branch');
    }
}
