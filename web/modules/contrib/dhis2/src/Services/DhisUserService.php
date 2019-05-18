<?php

namespace Drupal\dhis\Services;


class DhisUserService implements DhisUserServiceInterface
{
    private $loginService;
    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
    }

    public function me(array $credentials){

        return $this->loginService->testLogin($credentials);
    }
}