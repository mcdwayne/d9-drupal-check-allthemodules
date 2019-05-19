<?php

namespace Drupal\twig_extender_extras;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Example configuration override.
 */
class TwigExtenderExtrasOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('twig_sandbox_whitelisted_classes', $names)) {
      print_r($names);
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'TwigExtenderExtrasOverrides';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
