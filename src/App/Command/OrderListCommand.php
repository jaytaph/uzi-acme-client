<?php

namespace NoxLogic\App\Command;

use GuzzleHttp\Exception\ClientException;
use NoxLogic\Acme\Client;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderListCommand extends AcmeCommand {

    protected static $defaultName = 'order:list';

    protected function configure(): void {
        parent::configure();

        $this->setDescription('View orders');

        $this->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Account email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email = $input->getOption('email');
        if ($email === null) {
            $output->writeln('<error>Account email is required</error>');
            return Command::FAILURE;
        }

        try {
            $acme = Client::createFromInput($input);
            $data = $acme->orderList($email, $input->getOption('identifiers'));
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

        $output->writeln("A new order has been created.");
        $table = new Table($output);
        $table->setHeaders([
            'Location',
            'Status',
            'Expires',
            'Authorization URLs',
            'Finalize URL'
        ]);

        $table->addRow([
            "unknown",
            $data['status'],
            $data['expires'],
            print_r($data['authorizations'], true),
            $data['finalize']
        ]);
        $table->setVertical(true);
        $table->render();

        return Command::SUCCESS;
    }
}
