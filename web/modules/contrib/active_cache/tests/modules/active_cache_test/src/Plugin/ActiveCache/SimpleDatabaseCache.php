<?php

namespace Drupal\active_cache_test\Plugin\ActiveCache;

use Drupal\active_cache\Plugin\ActiveCacheBase;

/**
 * @ActiveCache(
 *  id = "simple_database",
 *  label = @Translation("Simple Database"),
 *  cache_tags = {"simple_database"},
 * )
 */
class SimpleDatabaseCache extends ActiveCacheBase {

  /**
   * {@inheritdoc}
   */
  protected function buildData() {
    return \Drupal::keyValue($this->getPluginId())->getAll();
  }

}
