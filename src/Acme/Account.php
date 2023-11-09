<?php

namespace NoxLogic\Acme;

use Jose\Component\Core\JWK;

class Account implements \JsonSerializable {
    protected string $contact;
    protected string $location;
    protected JWK $key;

    public function __construct(string $contact, string $location, JWK $key)
    {
        $this->contact = $contact;
        $this->location = $location;
        $this->key = $key;
    }

    static function fromJson(string $data): Account {
        $data = json_decode($data, true);

        $key = JWK::createFromJson(json_encode($data['key']));
        return new Account($data['contact'], $data['location'], $key);
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getKey(): JWK
    {
        return $this->key;
    }

    public function jsonSerialize()
    {
        return [
            'contact' => $this->contact,
            'location' => $this->location,
            'key' => $this->key,
        ];
    }
}
