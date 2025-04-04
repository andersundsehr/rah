<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'append',
    description: 'upload files to a rah server (adds to the existing files in the destination directory)',
    aliases: ['a'],
)]
class AppendCommand extends UploadCommand
{
    protected const ACTION = 'append';
}
