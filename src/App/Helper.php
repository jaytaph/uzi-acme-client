<?php

namespace NoxLogic\App;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class Helper {

    function printUserInfo(string $email, array $data, OutputInterface $output) {
        $output->writeln('<info>Account details for: <comment>' . $email . '</info>');

        $table = new Table($output);
        $table->setHeaders([
            'Key',
            'Contact',
            'Initial IP',
            'Created At',
            'Status'
        ]);

        $table->addRow([
            $data['key']['kty'] . '/' . $data['key']['crv'],
            implode("\n", $data['contact']),
            $data['initialIp'],
            $data['createdAt'],
            $data['status']
        ]);
        $table->setVertical(true);
        $table->render();
    }
}
