<?php

namespace Drupal\healthcheck\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining CheckConfig entities.
 */
interface CheckConfigInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the Healthcheck plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  public function getCheckCollection();

  /**
   * Gets the Healthcheck plugin.
   *
   * @return \Drupal\healthcheck\Plugin\HealthcheckPluginInterface
   */
  public function getCheck();
}
