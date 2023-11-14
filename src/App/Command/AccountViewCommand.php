<?php

namespace NoxLogic\App\Command;

use NoxLogic\Acme\Client;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use NoxLogic\App\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AccountViewCommand extends AcmeCommand {

    protected static $defaultName = 'account:view';

    protected function configure(): void {
        $this->setDescription('View account details');
        $this->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Account email');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email = $input->getOption('email');
        if ($email === null) {
            $output->writeln('<error>Account email is required</error>');
            return Command::FAILURE;
        }

        try {
            $acme = Client::createFromInput($input);
            $data = $acme->viewAccount($email);
        } catch (AccountNotFoundException) {
            $output->writeln('<error>Account not found</error>');
            return Command::FAILURE;
        }

        $helper = new Helper();
        $helper->printUserInfo($email, $data, $output);

        return Command::SUCCESS;
    }
}
