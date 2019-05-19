<?php

/**
 * @file
 * Store access data.
 * @todo Allow for installing the module at different locations.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\smart_ip\SmartIp;

chdir('../../..');

$autoloader = require_once 'autoload.php';

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);

$ip = $request->getClientIP();
$url = $request->server->get('HTTP_REFERER');
$location = SmartIp::query($ip);
$code = $location['countryCode'] ? $location['countryCode'] : 'XX';

$connection = \Drupal::database();
$connection->insert('sitelog_access')
  ->fields(array(
    'ip' => $ip,
    'url' => $url,
    'country' => $code,
    'logged' => REQUEST_TIME,
  ))
  ->execute();
