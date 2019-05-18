<?php

namespace Drupal\plugindecorator;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Base class for Plugin Manager Decorators.
 */
abstract class PluginManagerDecoratorBase implements PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  /**
   * The decorated plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface|\Drupal\Core\Cache\CacheableDependencyInterface
   */
  protected $decorated;

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return $this->decorated->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->decorated->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->decorated->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return $this->decorated->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->decorated->getInstance($options);
  }

  /**
   * @inheritDoc
   */
  public function getCacheContexts() {
    return $this->decorated->getCacheContexts();
  }

  /**
   * @inheritDoc
   */
  public function getCacheTags() {
    return $this->decorated->getCacheTags();
  }

  /**
   * @inheritDoc
   */
  public function getCacheMaxAge() {
    return $this->decorated->getCacheMaxAge();
  }

  /**
   * @inheritDoc
   */
  public function clearCachedDefinitions() {
    $this->decorated->clearCachedDefinitions();
  }

  /**
   * @inheritDoc
   */
  public function useCaches($use_caches = FALSE) {
    return $this->decorated->useCaches();
  }

}
