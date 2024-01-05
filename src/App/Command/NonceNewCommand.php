<?php

namespace NoxLogic\App\Command;

use NoxLogic\Acme\Client;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use NoxLogic\App\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NonceNewCommand extends AcmeCommand {

    protected static $defaultName = 'nonce:create';

    protected function configure(): void {
        parent::configure();

        $this->setDescription('Generate a new nonce');

        $this->addOption('acme-uri', 'u', InputOption::VALUE_REQUIRED, 'ACME server URI');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $acme = Client::createFromInput($input);
        $nonce = $acme->getNonce();

//        $output->writeln("None: <comment>$nonce</comment>");
        print json_encode(['nonce' => $nonce], JSON_PRETTY_PRINT);

        return Command::SUCCESS;
    }
}
