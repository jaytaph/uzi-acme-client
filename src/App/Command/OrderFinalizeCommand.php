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

class OrderFinalizeCommand extends AcmeCommand {

    protected static $defaultName = 'order:finalize';

    protected function configure(): void {
        parent::configure();

        $this->setDescription('View orders');

        $this->addOption('email', 'e', InputOption::VALUE_REQUIRED, 'Account email');
        $this->addOption('url', '', InputOption::VALUE_REQUIRED, 'Finalize url of order');
        $this->addOption('cert-file', 'c', InputOption::VALUE_REQUIRED, 'CSR cert file in DER format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $email = $input->getOption('email');
        if ($email === null) {
            $output->writeln('<error>Account email is required</error>');
            return Command::FAILURE;
        }

        $cert = file_get_contents($input->getOption('cert-file'));
        $cert = Base64::encode($cert);

        try {
            $acme = Client::createFromInput($input);
            $data = $acme->orderFinalize($email, $input->getOption('url'), $cert);
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
