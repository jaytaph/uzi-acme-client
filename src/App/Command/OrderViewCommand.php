<?php

namespace NoxLogic\App\Command;

use GuzzleHttp\Exception\ClientException;
use NoxLogic\Acme\Base64;
use NoxLogic\Acme\Client;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderViewCommand extends AcmeCommand {

    protected static $defaultName = 'order:view';

    protected function configure(): void {
        parent::configure();

        $this->setDescription('View orders');

        $this->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Account email');
        $this->addOption('url', '', InputOption::VALUE_REQUIRED, 'url of order');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email = $input->getOption('email');
        if ($email === null) {
            $output->writeln('<error>Account email is required</error>');
            return Command::FAILURE;
        }

        try {
            $acme = Client::createFromInput($input);
            $data = $acme->orderView($email, $input->getOption('url'));
        } catch (AccountNotFoundException) {
            $output->writeln('<error>Account not found</error>');

            return Command::FAILURE;
        } catch (ClientException $e) {
            var_dump($e->getResponse()->getBody()->getContents());
        } catch (\Exception $e) {
            $output->writeln('<error>Generic exception: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        print_r($data);

        return Command::SUCCESS;
    }
}
