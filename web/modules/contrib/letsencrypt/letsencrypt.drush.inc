<?php

/**
 * @file
 * Drush commands for Letsencrypt!
 */

use Drupal\letsencrypt\Utility\PrepareDomain;

/**
 * Implements hook_drush_command().
 */
function letsencrypt_drush_command() {
  $items['letsencrypt-cert'] = [
    'description' => 'Update LetsEncrypt Certs',
    'arguments' => ['world' => 'World term key'],
    'examples' => ['drush letsencrypt-cert RU' => 'Update RU cert'],
  ];
  return $items;
}

/**
 * Drush update cert command.
 */
function drush_letsencrypt_cert() {
  $config = \Drupal::config('letsencrypt.settings');
  $base = $config->get('domain-base');
  if ($base) {
    $domains = PrepareDomain::init($config->get('domain-domains'), TRUE);
    $le = \Drupal::service('letsencrypt')->sign($base, $domains);
    drush_print(dt("update cert for @base", ['@base' => $base]));
  }
  else {
    drush_print(dt("Config problem"));
  }
}
