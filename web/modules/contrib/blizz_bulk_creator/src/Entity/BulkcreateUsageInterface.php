<?php

namespace Drupal\blizz_bulk_creator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface BulkcreateUsageInterface.
 *
 * Defines the API for Bulkcreate usages.
 *
 * @package Drupal\blizz_bulk_creator\Entity
 */
interface BulkcreateUsageInterface extends ConfigEntityInterface {

  /**
   * Returns the active bulkcreate configuration.
   *
   * @return \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface
   *   The bulkcreate_configuration entity.
   */
  public function getBulkcreateConfiguration();

}
