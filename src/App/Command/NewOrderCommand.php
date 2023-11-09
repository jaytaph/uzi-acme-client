<?php

namespace NoxLogic\App\Command;

use GuzzleHttp\Exception\ClientException;
use NoxLogic\Acme\Client;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NewOrderCommand extends AcmeCommand {

    protected static $defaultName = 'order:new';

    protected function configure(): void {
        parent::configure();

        $this->setDescription('View account details');

        $this->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Account email');
        $this->addOption('identifiers', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Order identifiers type/value pairs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email = $input->getOption('email');
        if ($email === null) {
            $output->writeln('<error>Account email is required</error>');
            return Command::FAILURE;
        }

        try {
            $acme = Client::createFromInput($input);
            $data = $acme->newOrder($email, $input->getOption('identifiers'));
        } catch (AccountNotFoundException) {
            $output->writeln('<error>Account not found</error>');

            return Command::FAILURE;
        } catch (ClientException $e) {
            var_dump($e->getResponse()->getBody()->getContents());
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            $output->writeln('<error>Generic exception: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        var_dump($data);

        return Command::SUCCESS;
    }
}
