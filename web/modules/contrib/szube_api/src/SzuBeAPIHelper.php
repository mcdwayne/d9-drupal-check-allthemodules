<?php

namespace Drupal\szube_api;

/**
 * SzuBeAPIHelper.
 */
class SzuBeAPIHelper {

  /**
   * Get Configuration Name.
   */
  public static function getConfigName() {
    return 'youtubeapi.settingss';
  }

  /**
   * Get Configuration Object.
   * @param $editable boolean.
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

}
