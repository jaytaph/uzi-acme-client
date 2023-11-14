<?php

namespace NoxLogic\App\Command;

use GuzzleHttp\Exception\ClientException;
use NoxLogic\Acme\Client;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AuthViewCommand extends AcmeCommand {

    protected static $defaultName = 'auth:view';

    protected function configure(): void {
        parent::configure();

        $this->setDescription('View authorization details');

        $this->addOption('url', '', InputOption::VALUE_REQUIRED, 'URL of auth');
        $this->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email = $input->getOption('email');
        if ($email === null) {
            $output->writeln('<error>Account email is required</error>');
            return Command::FAILURE;
        }

        if (empty($input->getOption('url'))) {
            $output->writeln('<error>URL is required</error>');
            return Command::FAILURE;
        }

        $acme = Client::createFromInput($input);
        $data = $acme->viewAuth($email, $input->getOption('url'));

        print_r($data);

        return Command::SUCCESS;
    }
}
