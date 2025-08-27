<?php

declare(strict_types=1);

namespace App\Dto;

use App\Service\SettingsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class Settings
{
    public const string DEFAULT_DELETE_AFTER = '1m';

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
    ) {
    }

    /**
     * @param array<string, string> $options
     */
    public static function fromEnv(array $options): self
    {
        return new SettingsService()->fromEnv($options);
    }

    public static function addOptionsToCommand(Command $command): void
    {
        $command
            ->addOption('api', null, InputOption::VALUE_REQUIRED, 'rah server api (with http(s))')
            ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'project name')
            ->addOption('deployment', 'd', InputOption::VALUE_REQUIRED, 'deployment name')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, '(commit) message of the deployment')
            ->addOption('defaultDeployment', null, InputOption::VALUE_REQUIRED, 'default deployment name');
    }
}
