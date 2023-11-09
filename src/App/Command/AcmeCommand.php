<?php

namespace NoxLogic\App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class AcmeCommand extends Command {

    protected function configure(): void {
        $this->addOption('acme-uri', 'u', InputOption::VALUE_REQUIRED, 'ACME server URI');
        $this->addOption('debug', 'd', InputOption::VALUE_NONE, 'Show debug info (HTTP requests)');
    }
}
