<?php

namespace NoxLogic\Acme;

use GuzzleHttp\Client as GuzzleClient;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use NoxLogic\Acme\Exception\AccountNotFoundException;
use Symfony\Component\Console\Input\InputInterface;

class Client {
    protected GuzzleClient $client;
    protected Directory $directory;

    function __construct(string $baseUri, GuzzleClient $client = null)
    {
        if ($client === null) {
            $this->client = new GuzzleClient([
                'base_uri' => $baseUri,
                'timeout' => 10.0,
                'debug' => true,
            ]);
        } else {
            $this->client = $client;
        }

        $this->accountStore = new AccountStore("./accounts");
        $this->directory = new Directory($this->getJson('/directory'));
    }

    public static function createFromInput(InputInterface $input)
    {
        $baseUri = $input->getOption('acme-uri') ?? $_SERVER['UZI_ACME_URI'] ?? 'https://acme-staging-v02.api.letsencrypt.org';

        $opts = [
            'base_uri' => $baseUri,
            'timeout' => 10,
            'connect_timeout' => 10,
            'debug' => $input->getOption('debug')
        ];

        $guzzle = new GuzzleClient($opts);
        return new Client($baseUri, $guzzle);
    }

    public function getNonce(): string
    {
        $url = $this->directory->getEntry(Directory::NEW_NONCE);
        $response = $this->client->head($url);

        $nonce = $response->getHeader('Replay-Nonce')[0];
        print_r($nonce);
        return $nonce;
    }

    public function newAccount(string $contact, bool $tosAgreed): array
    {
        $account = $this->accountStore->loadAccount($contact);
        if (!$account) {
            $key = JWKFactory::createECKey('P-256');
            $account = new Account($contact, '', $key);
        }

        $payload = [
            'contact' => [
                "mailto:" . $account->getContact(),
            ],
            'termsOfServiceAgreed' => $tosAgreed,
            'onlyReturnExisting' => false,
            'externalAccountBinding' => null,
        ];

        $url = $this->directory->getEntry(Directory::NEW_ACCOUNT);
        $json = $this->createJsonForUrl($account, $url, $payload, asJwk: true);

        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        // Retrieve location and store in account before saving
        $location = $response->getHeader('Location')[0];
        $account->setLocation($location);
        $this->accountStore->saveAccount($contact, $account);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function viewAuth(string $contact, string $url): array
    {
        $account = $this->accountStore->loadAccount($contact);
        if (!$account) {
            throw new AccountNotFoundException("Account not found");
        }

        $json = $this->createJsonForUrl($account, $url, []);
        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function acceptChallenge(string $contact, string $url, string $token, string $cert, string $f9cert): array
    {
        $account = $this->accountStore->loadAccount($contact);
        if (!$account) {
            throw new AccountNotFoundException("Account not found");
        }

        if ($token[0] == '@') {
            $token = file_get_contents(substr($token, 1));
            $token = str_replace("\n", "", $token);
            $token = str_replace(" ", "", $token);
        }
        if ($cert[0] == '@') {
            $cert = file_get_contents(substr($cert, 1));
            $cert = str_replace("-----END CERTIFICATE-----", "", $cert);
            $cert = str_replace("-----BEGIN CERTIFICATE-----", "", $cert);
            $cert = str_replace("\n", "", $cert);
        }
        if ($f9cert[0] == '@') {
            $f9cert = file_get_contents(substr($f9cert, 1));
            $f9cert = str_replace("-----END CERTIFICATE-----", "", $f9cert);
            $f9cert = str_replace("-----BEGIN CERTIFICATE-----", "", $f9cert);
            $f9cert = str_replace("\n", "", $f9cert);
        }

        $json = $this->createJsonForUrl($account, $url, [], encodeEmptyPayload: true);
        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
                'X-Acme-Jwt' => $token,
                'X-Acme-Cert' => $cert,
                'X-Acme-F9Cert' => $f9cert,
            ],
            'body' => $json,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function orderFinalize(string $contact, string $url, string $csr)
    {
        $account = $this->accountStore->loadAccount($contact);
        if (!$account) {
            throw new AccountNotFoundException("Account not found");
        }

        $payload = [
            'csr' => $csr,
        ];

        $json = $this->createJsonForUrl($account, $url, $payload);
        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function orderView(string $contact, string $url)
    {
        $account = $this->accountStore->loadAccount($contact);
        if (!$account) {
            throw new AccountNotFoundException("Account not found");
        }

        $json = $this->createJsonForUrl($account, $url, []);
        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function getAccountKey(string $contact, bool $generate = false): JWK
    {
        if (! file_exists("$contact.account") && ! $generate) {
            throw new AccountNotFoundException();
        }

        if (file_exists("$contact.account")) {
            $data = file_get_contents("$contact.account");
            return JwkFactory::createFromJsonObject($data);
        }

        $key = JWKFactory::createECKey('P-256');
        file_put_contents(
            "$contact.account",
            json_encode($key, JSON_PRETTY_PRINT)
        );

        return $key;
    }

    public function viewAccount(string $contact): array
    {
        $account = $this->accountStore->loadAccount($contact);
        if (!$account) {
            throw new AccountNotFoundException("Account not found");
        }

        $payload = [
            'contact' => [
                "mailto:" . $account->getContact(),
            ],
            'onlyReturnExisting' => true,
        ];

        $url = $this->directory->getEntry(Directory::NEW_ACCOUNT);
        $json = $this->createJsonForUrl($account, $url, $payload, asJwk: true);

        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        $url = $response->getHeader('Location')[0];
        $json = $this->createJsonForUrl($account, $url, []);
        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        print_r($response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true);
    }

    public function newOrder(string $contact, array $identifiers): array
    {
        $account = $this->accountStore->loadAccount($contact);
        if (!$account) {
            throw new AccountNotFoundException("Account not found");
        }

        $payload = [
            'identifiers' => [],
            'notBefore' => null,
            'notAfter' => null,
        ];

        foreach ($identifiers as $id) {
            $id = explode(':', $id, 2);
            $payload['identifiers'][] = [
                'type' => $id[0],
                'value' => $id[1],
            ];
        }

        $url = $this->directory->getEntry(Directory::NEW_ORDER);
        $json = $this->createJsonForUrl($account, $url, $payload);

        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        $location = $response->getHeader('Location')[0];

        return [$location, json_decode($response->getBody()->getContents(), true)];
    }

    protected function createJsonForUrl(Account $account, string $url, array $payload, bool $asJwk = false, bool $encodeEmptyPayload = false): string
    {
        $protected = [
            'alg' => 'ES256',
            'nonce' => $this->getNonce(),
            'url' => $url,
        ];
        if ($asJwk) {
            $protected['jwk'] = $account->getKey()->toPublic();
        } else {
            $protected['kid'] = $account->getLocation();
        }

        $jws = $this->signJws($protected, $payload, $account->getKey(), $encodeEmptyPayload);

        $serializer = new JSONFlattenedSerializer();
        return $serializer->serialize($jws, 0);
    }

    protected function signJws(array $protectedHeaders, array $payload, JWK $jwk, bool $encodeEmptyPayload): JWS
    {
        if ($protectedHeaders['alg'] != 'ES256') {
            throw new \Exception('Only ES256 is supported');
        }

        $manager = new AlgorithmManager([new ES256()]);

        var_dump($payload);

        if (count($payload)) {
            $payload = json_encode($payload);
        } else {
            if ($encodeEmptyPayload) {
                // Sometimes we need to encode an empty payload (for instance, when accepting a challenge)
                $payload = "{}";
            } else {
                $payload = "";
            }
        }

//        var_dump($payload);

        $jwsBuilder = new JWSBuilder($manager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, $protectedHeaders)
            ->build();

        return $jws;
    }

    protected function getJson(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception) {
            $data = [];
        }

        return $data;
    }

}
