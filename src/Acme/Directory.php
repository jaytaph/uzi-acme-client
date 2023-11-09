<?php

namespace NoxLogic\Acme;

class Directory {
    protected array $directory = [];

    public const NEW_ACCOUNT = 'newAccount';
    public const NEW_ORDER = 'newOrder';
    public const NEW_NONCE = 'newNonce';
    public const REVOKE_CERT = 'revokeCert';
    public const RENEWAL_INFO = 'renewalInfo';

    function __construct(array $directory) {
        $this->directory = $directory;
    }

    function getEntry(string $entry): string {
        return $this->directory[$entry] ?? '';
    }
}
