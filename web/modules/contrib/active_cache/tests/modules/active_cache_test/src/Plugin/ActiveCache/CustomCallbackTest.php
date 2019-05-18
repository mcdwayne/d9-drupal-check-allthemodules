<?php

namespace Drupal\active_cache_test\Plugin\ActiveCache;

use Drupal\active_cache\Plugin\ActiveCacheBase;
use Drupal\Core\Cache\CacheBackendInterface;

class CustomCallbackTest extends ActiveCacheBase {

  /**
   * @var callable
   */
  protected $callback;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache_backend, CacheBackendInterface $static_cache, array $configuration, $plugin_id, $plugin_definition) {
    $this->callback = $configuration['data_callback'];
    assert('is_callable($this->callback) || (is_array($this->callback) && count($this->callback) == 2)', 'data_callback must be callable');
    parent::__construct($cache_backend, $static_cache, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildData() {
    return call_user_func($this->callback);
  }

}
