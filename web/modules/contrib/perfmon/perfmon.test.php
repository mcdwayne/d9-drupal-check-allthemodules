<?php

/**
 * @file
 * Simple bootstrap drupal for test.
 */
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once __DIR__ . '/autoload.php';
$request = Request::createFromGlobals();

$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
