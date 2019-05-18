<?php

namespace Drupal\ovh;

use Ovh\Api;
use Drupal\ovh\Entity\OvhKey;

/**
 * OVH Helper functions.
 */
class OvhHelper {

  /**
   * Get Configuration Name.
   */
  public static function getConfigName() {
    return 'ovh.settings';
  }

  /**
   * Get Configuration Object.
   *
   * @param bool $editable
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  public static function getConfig($editable = FALSE) {
    if ($editable) {
      $config = \Drupal::configFactory()->getEditable(static::getConfigName());
    }
    else {
      $config = \Drupal::config(static::getConfigName());
    }
    return $config;
  }

  /**
   * Get the ovh api connection.
   *
   * @return \Ovh\Api|null
   */
  public static function getOvh($key_id = NULL) {
    $conn = NULL;
    $apikey = NULL;

    if ($key_id) {
      $apikey = OvhKey::load($key_id);
    }

    // Retry default.
    if (!$key_id || !$apikey) {
      $config = OvhHelper::getConfig();
      $key_id = $config->get('default_apikey');
      $apikey = OvhKey::load($key_id);
    }

    if ($apikey) {
      $conn = new Api($apikey->get('app_key'), $apikey->get('app_sec'), $apikey->get('endpoint'), $apikey->get('con_key'));
    }

    return $conn;
  }

  /**
   * Get the ovh application connection.
   *
   * @return \Ovh\Api|null
   */
  public static function getOvhApplicationAuth($key_id = NULL) {
    $conn = NULL;
    if (!$key_id) {
      $config = OvhHelper::getConfig();
      $key_id = $config->get('default_apikey');
    }
    $apikey = OvhKey::load($key_id);
    if ($apikey) {
      $conn = new Api($config->get('app_key'), $config->get('app_sec'), $apikey->get('endpoint'));
    }
    return $conn;
  }

  /**
   * Run Api GET .
   *
   * @return array
   */
  public static function ovhGet($path, $key_id = NULL) {
    $ovh = self::getOvh($key_id);
    $result = $ovh->get($path);
    return $result;
  }

  /**
   * Run Api POST .
   *
   * @return array
   */
  public static function ovhPost($path, $data, $key_id = NULL) {
    $ovh = self::getOvh($key_id);
    $result = $ovh->post($path, $data);
    return $result;
  }

  /**
   * Run Api PUT .
   *
   * @return array
   */
  public static function ovhPut($path, $key_id = NULL) {
    $ovh = self::getOvh($key_id);
    $result = $ovh->put($path);
    return $result;
  }

  /**
   * Run Api DELETE .
   *
   * @return array
   */
  public static function ovhDelete($path, $key_id = NULL) {
    $ovh = self::getOvh($key_id);
    $result = $ovh->delete($path);
    return $result;
  }

}
