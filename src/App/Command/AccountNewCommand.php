<?php

namespace NoxLogic\App\Command;

use NoxLogic\Acme\Client;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use NoxLogic\App\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AccountNewCommand extends AcmeCommand {

    protected static $defaultName = 'account:new';

    protected function configure(): void {
        parent::configure();
        $this->setDescription('Create new account');

        $this->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Account email');
        $this->addOption('tos', 't', InputOption::VALUE_NONE, 'Terms-Of-Service accepted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email = $input->getOption('email');
        if ($email === null) {
            $output->writeln('<error>Account email is required</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>New account for ' . $email . '</info>');

        try {
            $acme = Client::createFromInput($input);
            $data = $acme->newAccount($email, $input->getOption('tos') ?? false);
        } catch (AccountNotFoundException) {
            $output->writeln('<error>Account not found</error>');
            return Command::FAILURE;
        }

        print_r($data);

        $helper = new Helper();
        $helper->printUserInfo($email, $data, $output);

        return Command::SUCCESS;
    }
}
