<?php

use Symfony\Component\Console\Application;

require "vendor/autoload.php";

$application = new Application("UZI ACME Client", "0.1.0");
$application->add(new NoxLogic\App\Command\AccountNewCommand());
$application->add(new NoxLogic\App\Command\AccountViewCommand());
$application->add(new NoxLogic\App\Command\NonceNewCommand());
$application->add(new NoxLogic\App\Command\OrderNewCommand());
$application->add(new NoxLogic\App\Command\OrderListCommand());
$application->add(new NoxLogic\App\Command\AuthViewCommand());
$application->add(new NoxLogic\App\Command\AuthAcceptCommand());

$application->run();
