<?php

/**
 * @file
 */

namespace Drupal\smsc\Smsc;

use Smsc\Settings\Settings;

/**
 * Class Settings
 *
 * @package Smsc\Settings
 */
class DrupalSmscSettings {

  /**
   * Initialize settings.
   *
   * @return Settings
   */
  public static function init() {
    $smscConfig = \Drupal::config('smsc.config');

    $host   = $smscConfig->get('host');
    $sender = $smscConfig->get('sender');

    $options = [
      'login' => $smscConfig->get('login'),
      'psw'   => $smscConfig->get('psw'),
    ];

    if (isset($host)) {
      $options['host'] = $host;
    }

    if (isset($sender)) {
      $options['sender'] = $sender;
    }

    $drupalRequest = new DrupalSmscRequest();

    $settings = new Settings($options, $drupalRequest);

    return $settings;
  }
}
