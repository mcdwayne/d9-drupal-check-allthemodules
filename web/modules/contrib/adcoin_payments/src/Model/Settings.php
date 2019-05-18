<?php
/**
 * Model containing getter methods for the module's configuration.
 * @author appels
 */

namespace Drupal\adcoin_payments\Model;
use Drupal\Core\Config;

class Settings {
  /**
   * Retrieves value of the API key setting.
   *
   * @return string AdCoin Wallet API key.
   * @return bool   False if the API key was not set or of invalid format.
   */
  public static function fetchApiKey() {
    $api_key = \Drupal::config('adcoin_payments.settings')->get('api_key');
    return (64 == strlen($api_key)) ? $api_key : false;
  }
}