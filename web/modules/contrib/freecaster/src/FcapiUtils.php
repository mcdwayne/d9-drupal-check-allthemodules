<?php

namespace Drupal\freecaster;

use Drupal\freecaster\Fcapi\FCAPI;

/**
 * This class contains helpers to work with FACPI.
 */
class FcapiUtils {

  /**
   * Call the Freecaster API.
   */
  public static function fcApiCall($method, $params = array(), &$num_records = NULL) {

    $config = \Drupal::service('config.factory')->get('freecaster.settings');

    static $fcapi;

    $cache_key = $method . '_' . md5(serialize($params));
    if (substr($method, 0, 4) == 'get_') {
      $cache = \Drupal::cache()->get($cache_key);
      if (($cache) && ($cache->data) && (isset($cache->data['res']))) {
        if (isset($cache->data['n'])) {
          $num_records = $cache->data['n'];
        }
        return $cache->data['res'];
      }
    }

    if (empty($fcapi)) {
      // Get the key and secret from the Drupal settings.
      $fcapi_uid = $config->get('fc_user_id');
      $fcapi_key = $config->get('fc_user_key');

      if (!$fcapi_uid || !$fcapi_key) {
        throw new Exception(t("Configure your Freecaster API credentials first"));
      }
      $fcapi = new FCAPI($fcapi_uid, $fcapi_key);
    }

    $data = array();

    $data['res'] = call_user_func(array($fcapi, $method), $params);

    if ($fcapi->num_records !== NULL) {
      $num_records = $data['n'] = $fcapi->num_records;
    }
    if (substr($method, 0, 4) == 'get_') {
      \Drupal::cache()->set($cache_key, $data, 'cache', time() + $config->get('fc_cache_ttl'));
    }
    return $data['res'];
  }

  /**
   *
   */
  public static function checkApiCredentials() {
    $config = \Drupal::service('config.factory')->get('freecaster.settings');
    $fcapi_uid = $config->get('fc_user_id');
    $fcapi_key = $config->get('fc_user_key');

    if (!$fcapi_uid || !$fcapi_key) {
      drupal_set_message(t("Configure your Freecaster API credentials first"), 'error');
    }

    return new FCAPI($fcapi_uid, $fcapi_key);
  }

}
