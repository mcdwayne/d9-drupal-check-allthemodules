<?php

namespace Drupal\active_cache\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Active cache plugins.
 */
abstract class ActiveCacheBase extends PluginBase implements ActiveCacheInterface, ContainerFactoryPluginInterface {

  use UseCacheBackendTrait;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $staticCache;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache_backend, CacheBackendInterface $static_cache, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cacheBackend = $cache_backend;
    $this->staticCache = $static_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $cache_bin = $plugin_definition['cache_bin'];
    return new static(
      $container->get("cache.{$cache_bin}"),
      $container->get('cache.static'),
      $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildCache() {
    $data = $this->buildData();
    $this->cacheSet($this->getCacheId(), $data, $this->getCacheMaxAge(), $this->getCacheTags());
    $this->staticCache->set($this->getCacheId(), $data, $this->getCacheMaxAge(), $this->getCacheTags());
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    if ($cache = $this->getCache()) {
      return $cache->data;
    }
    else {
      return $this->buildCache();
    }
  }

  public function getCache() {
    if ($cache = $this->staticCache->get($this->getCacheId())) {
      return $cache;
    }
    else {
      return $this->cacheGet($this->getCacheId());
    }
  }

  /**
   * @return bool
   *   Is the data currently being cached?
   */
  public function isCached() {
    return (bool) $this->getCache();
  }

  /**
   * @return string
   */
  public function getCacheId() {
    return $this->getPluginDefinition()['cache_id'];
  }

  /**
   * @return int
   */
  public function getCacheMaxAge() {
    return $this->getPluginDefinition()['max_age'];
  }

  /**
   * @return string[]
   */
  public function getCacheTags() {
    return $this->getPluginDefinition()['cache_tags'];
  }

  /**
   * @return string[]
   */
  public function getCacheContexts() {
    return $this->getPluginDefinition()['cache_contexts'];
  }

  /**
   * Builds the data that will be cached.
   *
   * @return mixed
   */
  protected abstract function buildData();

}
