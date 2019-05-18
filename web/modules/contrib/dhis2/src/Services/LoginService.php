<?php

/**
 * @file
 */

namespace Drupal\dhis\Services;

interface LoginService
{
    public function login($url);
    public function testLogin(array $credentials);
}