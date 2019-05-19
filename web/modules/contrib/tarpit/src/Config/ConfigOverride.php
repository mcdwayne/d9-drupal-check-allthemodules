<?php
/**
 * @file
 * Contains \Drupal\tarpit\Config\ConfigOverride.
 */

namespace Drupal\tarpit\Config;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

class ConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();
    if (in_array('tarpit.config', $names)) {
      $overrides['tarpit.config']['wordlist'] = drupal_get_path('module', 'tarpit') . '/assets/words.txt';
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'TarpitConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  public function getCacheableMetadata($name) {
  }

}
