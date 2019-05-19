<?php

/**
 * @file
 * Provides Drupal\themekey\PropertyBase.
 */

namespace Drupal\themekey;

use Drupal\themekey\Plugin\SingletonPluginBase;

abstract class PropertyBase extends SingletonPluginBase implements PropertyInterface {

  protected $engine;

  public function setEngine($engine) {
    $this->engine = $engine;
  }

  /**
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  public function getRouteMatch() {
    return $this->engine->getRouteMatch();
  }

  /**
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  public function getConfigFactory() {
    return $this->$engine->getConfigFactory();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isPageCacheCompatible() {
    return $this->pluginDefinition['page_cache_compatible'];
  }
}

