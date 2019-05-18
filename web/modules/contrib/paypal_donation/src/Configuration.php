<?php

namespace Drupal\paypal_donation;

/**
 * Class Configuration.
 *
 * @package Drupal\paypal_donation
 */
class Configuration {

  /**
   * PayPal SDK Configuration.
   *
   * For a full list of configuration parameters refer in wiki page
   * (https://github.com/paypal/sdk-core-php/wiki/Configuring-the-SDK).
   *
   * @return array
   *   Returns array with configuration settings.
   */
  public static function getConfig() {
    $config = \Drupal::config('paypal_donation.settings');
    $paypal_config = [
      "acct1.UserName" => $config->get('api_username'),
      "acct1.Password" => $config->get('api_password'),
      "acct1.Signature" => $config->get('api_signature'),
    ];
    if ($config->get('sandbox')) {
      $paypal_config['mode'] = 'sandbox';
    }
    else {
      $paypal_config['mode'] = 'live';
    }

    return $paypal_config;

  }

}
