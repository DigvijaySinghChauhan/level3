<?php

namespace Level3\Tests\Processor\Wrapper\Authentication;

use Level3\Processor\Wrapper\Authentication\AuthenticatedCredentials;
use Level3\Processor\Wrapper\Authorization\Role;

class AuthenticatedCredentialsBuilder
{
    const IRRELEVANT_ID = 'X';
    const IRRELEVANT_LOGIN = 'XX';
    const IRRELEVANT_FULL_NAME = 'XXX';
    const IRRELEVANT_SECRET_KEY = 'Y';
    const IRRELEVANT_API_KEY = 'YY';

    private $id = self::IRRELEVANT_ID;
    private $login = self::IRRELEVANT_LOGIN;
    private $fullName = self::IRRELEVANT_FULL_NAME;
    private $secretKey = self::IRRELEVANT_SECRET_KEY;
    private $apiKey = self::IRRELEVANT_API_KEY;
    private $role;

    private function __construct(Role $role)
    {
        $this->role = $role;
    }

    public static function anAuthenticatedUser()
    {
        return new static(new Role());
    }

    public static function withIrrelevantFields()
    {
        return static::anAuthenticatedUser()
            ->withId(static::IRRELEVANT_ID)
            ->withLogin(static::IRRELEVANT_LOGIN)
            ->withFullName(static::IRRELEVANT_FULL_NAME)
            ->withSecretKey(static::IRRELEVANT_SECRET_KEY)
            ->withApiKey(static::IRRELEVANT_API_KEY);
    }

    public function build()
    {
        return new AuthenticatedCredentials($this->id, $this->login, $this->fullName, $this->role, $this->secretKey, $this->apiKey);
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withLogin($login)
    {
        $this->login = $login;
        return $this;
    }

    public function withFullName($fullName)
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function withRole(Role $role)
    {
        $this->role = $role;
        return $this;
    }

    public function withSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    public function withApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }
}