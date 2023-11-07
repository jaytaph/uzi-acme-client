<?php

use GuzzleHttp\Client;
use Jthijssen\AcmeClient\AcmeClient;

require "vendor/autoload.php";

$acme = new AcmeClient('http://localhost:4001');
//$acme = new AcmeClient('https://acme-staging-v02.api.letsencrypt.org');
$account = $acme->newAccount('john@deadcode.nl', tosAgreed: true);

