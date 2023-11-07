<?php

namespace Jthijssen\AcmeClient;

use GuzzleHttp\Client;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\JSONFlattenedSerializer;
use Jose\Component\Signature\Serializer\Serializer;

class AcmeClient {
    protected Client $client;
    protected ?array $directoryCache = null;

    function __construct(string $baseUri) {

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 10.0,
            'debug' => true,
        ]);
    }

    function getDirectory(): array {
        if ($this->directoryCache === null) {
            $this->directoryCache = $this->getJson('/directory');
        }

        return $this->directoryCache;
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

    public function getNonce(): string
    {
        $url = $this->getDirectory()['newNonce'];
        $response = $this->client->head($url);

        $nonce = $response->getHeader('Replay-Nonce')[0];
        return $nonce;
    }

    public function newAccount(string $contact, bool $tosAgreed): void
    {
        $jwk = $this->generateOrFetchJWK($contact);

        $payload = [
            'contact' => [
                "mailto:$contact",
            ],
            'termsOfServiceAgreed' => $tosAgreed,
            'onlyReturnExisting' => false,
            'externalAccountBinding' => null,
        ];

        $url = $this->getDirectory()['newAccount'];

        $protected = [
            'alg' => 'ES256',
            'nonce' => $this->getNonce(),
            'url' => $url,
            'jwk' => $jwk->toPublic(),
        ];

        $jws = $this->signJws($protected, $payload, $jwk);

        $serializer = new JSONFlattenedSerializer();
        $json = $serializer->serialize($jws, 0);
        print_r($json);

        print("======================================================\n");
        $response = $this->client->post($url, [
            'headers' => [
                'Content-Type' => 'application/jose+json',
            ],
            'body' => $json,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        print_r($data);
    }

    protected function signJws(array $protectedHeaders, array $payload, JWK $jwk): JWS
    {
        if ($protectedHeaders['alg'] != 'ES256') {
            throw new \Exception('Only ES256 is supported');
        }

        $manager = new AlgorithmManager([new ES256()]);

        $jwsBuilder = new JWSBuilder($manager);
        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($jwk, $protectedHeaders)
            ->build();

        return $jws;
    }

    protected function generateOrFetchJWK(string $contact): JWK
    {
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
}
