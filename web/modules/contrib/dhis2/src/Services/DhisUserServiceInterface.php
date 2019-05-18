<?php

namespace Drupal\dhis\Services;


interface DhisUserServiceInterface
{
    public function me(array $credentials);
}