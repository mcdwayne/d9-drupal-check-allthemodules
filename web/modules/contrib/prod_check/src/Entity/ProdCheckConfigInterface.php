<?php

namespace Drupal\prod_check\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a prod check entity.
 */
interface ProdCheckConfigInterface extends ConfigEntityInterface {

  /**
   * Returns whether or not this processor is configurable.
   *
   * @return bool
   */
  public function isConfigurable();

  /**
   * Returns the operation plugin.
   *
   * @return \Drupal\prod_check\Plugin\ProdCheckInterface
   */
  public function getPlugin();

}
