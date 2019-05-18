<?php

namespace Drupal\active_cache_test\Plugin\ActiveCache;

use Drupal\active_cache\Plugin\ActiveCacheBase;
use Drupal\Component\Utility\Random;

/**
 * @ActiveCache(
 *  id = "random_data",
 *  label = @Translation("Test Cache"),
 *  cache_tags = {"random_data"},
 * )
 */
class RandomDataCache extends ActiveCacheBase {

  /**
   * {@inheritdoc}
   */
  protected function buildData() {
    $random = new Random();
    $data = array_map([$random, 'object'], range(1, 128));
    return $data;
  }

}
