<?php

/**
 * @file
 * Allows Node to make command line requests without a web server.
 */

use Symfony\Component\HttpFoundation\Request;
use Drupal\user\Entity\User;

if (php_sapi_name() !== 'cli') {
  die;
}

$input_data = file_get_contents('php://stdin');
$input = json_decode($input_data, TRUE);

if (!is_array($input) || empty($input['path'])) {
  throw new \Exception('Invalid input.');
}

$admin = User::load(1);

if (!$admin) {
  throw new \Exception('User 1 does not exit.');
}

\Drupal::service('account_switcher')->switchTo($admin);

if (!empty($input['content'])) {
  $request = Request::create($input['path'], 'POST', [], [], [], [], $input['content']);
}
else {
  $request = Request::create($input['path'], 'GET');
}

$response = \Drupal::service('http_kernel')->handle($request);

if (!$response->isOk()) {
  throw new \Exception("Request to {$input['path']} failed.");
}

echo json_encode($response->getContent());
