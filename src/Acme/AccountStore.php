<?php

namespace NoxLogic\Acme;

class AccountStore {
    protected $rootPath = "./accounts";

    function __construct(string $rootPath = null) {
        $this->rootPath = $rootPath ?? $this->rootPath;
    }

    function hasAccount(string $contact): bool {
        return file_exists($this->rootPath . '/' . $contact . '/account.json');
    }

    function loadAccount(string $contact): ?Account {
        $data = @file_get_contents($this->rootPath . '/' . $contact . '/account.json');
        if (!$data) {
            return null;
        }

        return Account::fromJson($data);
    }

    function saveAccount(string $contact, Account $account) {
        mkdir($this->rootPath . '/' . $contact, 0777, true);

        file_put_contents($this->rootPath . '/' . $contact . '/account.json', json_encode($account, JSON_PRETTY_PRINT));
    }
}
