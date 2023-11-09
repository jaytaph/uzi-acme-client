<?php

use Symfony\Component\Console\Application;

require "vendor/autoload.php";

$application = new Application("UZI ACME Client", "0.1.0");
$application->add(new NoxLogic\App\Command\NewAccountCommand());
$application->add(new NoxLogic\App\Command\ViewAccountCommand());
$application->add(new NoxLogic\App\Command\CreateNonceCommand());
$application->add(new NoxLogic\App\Command\NewOrderCommand());

$application->run();
