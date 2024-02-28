<?php

namespace AtpCore\Laminas\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    abstract function getInputVariables(): array;

    /**
     * Validate input parameters of command;
     * Since option-parameters are ALWAYS optional, even if REQUIRED-mode is explicitly set
     */
    public function isValid(SymfonyCommand $command, InputInterface $input, OutputInterface $output): bool
    {
        // Initialize valid
        $valid = true;

        // Get input-variables of command
        $inputVariables = $command->getInputVariables();
        if (empty($inputVariables)) return $valid;

        // Iterate input-variables
        foreach($inputVariables AS $inputVariable) {
            // Check if argument/option is required and provided
            switch (get_class($inputVariable)) {
                case InputArgument::class:
                    if ($inputVariable->isRequired() === true && empty($input->getArgument($inputVariable->getName()))) {
                        $output->writeln("<error>Required argument \"{$inputVariable->getName()}\" not provided</error>");
                        $output->writeln("<info>Usage: {$command->getSynopsis()}</info>");
                        $valid = false;
                    }
                    break;
                case InputOption::class:
                    if ($inputVariable->isValueRequired() === true && empty($input->getOption($inputVariable->getName()))) {
                        $output->writeln("<error>Required option \"{$inputVariable->getName()}\" not provided</error>");
                        $output->writeln("<info>Usage: {$command->getSynopsis()}</info>");
                        $valid = false;
                    }
                    break;
                default:
                    $output->writeln("<error>Unsupported variable-type (" . get_class($inputVariable) . ") provided for {$inputVariable->getName()}</error>");
                    $output->writeln("<info>Usage: {$command->getSynopsis()}</info>");
                    $valid = false;
                    break;
            }
        }

        // Return
        return $valid;
    }
}
