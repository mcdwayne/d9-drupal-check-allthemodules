<?php

/**
 * @file
 * Handles counts of node views via AJAX with minimal bootstrap.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

chdir('../../..');

$autoloader = require_once 'autoload.php';

$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

if (isset($_POST['caller_number'])) {
  $config = $container->get('config.factory')->get('onlinepbx.settings');
  $caller = substr($_POST['caller_number'], -10);
  $responce = [
    'transfer' => onlinepbx_transfer($config, $_POST['trunk']),
  ];
  $phones = onlinepbx_get_blacklist($config);
  if (isset($phones[$caller])) {
    $responce = [
      'set_name' => 'Чёрный список',
      'transfer' => '5000',
    ];
    \Drupal::logger('onlinepbx_blacklist')->notice($caller);
  }
}
else {
  $message = "<pre>" . print_r($_POST, TRUE) . "</pre>";
  \Drupal::logger('onlinepbx')->notice($message);
}

print onlinepbx_print_responce($responce);

/**
 * Transfer.
 */
function onlinepbx_transfer($config, $trunk) {
  $transfer = $config->get('transfer-default');
  $gateways = Yaml::parse($config->get('transfer'));
  if (isset($gateways[$trunk])) {
    $transfer = $gateways[$trunk];
  }
  return $transfer;
}

/**
 * Print responce.
 */
function onlinepbx_print_responce($responce) {
  $result = "";
  if (!empty($responce)) {
    foreach ($responce as $key => $value) {
      $result .= "{$key}: \"{$value}\"\n";
    }
  }
  return $result;
}

/**
 * BlackList get phones.
 */
function onlinepbx_get_blacklist($config) {

  $phones = [];
  if ($config->get('black-on') && $config->get('black-phones')) {
    $phonelines = explode("\n", $config->get('black-phones'));
    foreach ($phonelines as $phoneline) {
      $phone = preg_replace('/[^0-9]/', '', $phoneline);
      if (strlen($phone) == 10) {
        $phones[$phone] = $phoneline;
      }
    }
  }
  return $phones;
}
