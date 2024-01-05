<?php

$email = $_SERVER['argv'][1];

print "\n";
print "Creating order...\n";
exec("phpnx uzi-acme.php order:new --email $email -i jwt:not-used", $output);
$json = json_decode(implode("\n", $output), true);
$auth_url = $json['authorizations'][0];
$finalize_url = $json['finalize'];
$parts = explode('/', $auth_url);
$order_number = $parts[count($parts) - 1];
print "  Auth URL: $auth_url \n";
print "  Finalize URL: $finalize_url \n";
print "  Order number: $order_number \n";


print "\n";
print "Getting challenge...\n";
$output = [];
exec("phpnx uzi-acme.php auth:view --email $email --url $auth_url", $output);
$json = json_decode(implode("\n", $output), true);
$challenge_url = $json['challenges'][0]['url'];
$token = $json['challenges'][0]['token'];
print "  Challenge URL: ". $challenge_url . "\n";
print "  Token: ". $token . "\n";


print "\n";
print "Creating JWT with acme token...\n";
$output = [];
exec("phpnx gen-jwt.php $token", $output);
file_put_contents("challenge.txt", $output);


print "\n";
print "Accepting challenge...\n";
$output = [];
exec("phpnx uzi-acme.php auth:accept --email $email --url $challenge_url --token @challenge.txt --cert @rick-attest.pem --f9cert f9-certificate.pem", $output);
$json = json_decode(implode("\n", $output), true);
print "  Status: ". $json['status'] . "\n";


print "\n";
print "Getting challenge result...\n";
$output = [];
exec("phpnx uzi-acme.php auth:view --email $email --url $auth_url", $output);
$json = json_decode(implode("\n", $output), true);
if ($json['challenges'][0]['status'] != 'valid') {
    print_r($json);
    exit(1);
}
print "  Status: " . $json['challenges'][0]['status']. " \n";


print "\n";
print "Finalizing order...\n";
$output = [];
exec("phpnx uzi-acme.php order:finalize --email $email --url $finalize_url --csr-cert-file rick-csr.der", $output);
$json = json_decode(implode("\n", $output), true);
if ($json['status'] != 'valid') {
    print "  Status: ". $json['status'] . "\n";
    print "  Error: ". $json['error'] . "\n";
    exit(1);
}
print "  Status: " . $json['status']. " \n";


print "\n";
print "Fetching certificate...\n";
$output = [];
$cert = file_get_contents($json['certificate'], false, stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]));
file_put_contents("order-$order_number.cert", $cert);

print "All done.";
