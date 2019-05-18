<?php

namespace Drupal\blizz_bulk_creator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface BulkcreateConfigurationInterface.
 *
 * Defines the API for Bulkcreate configurations.
 *
 * @package Drupal\blizz_bulk_creator\Entity
 */
interface BulkcreateConfigurationInterface extends ConfigEntityInterface {

  /**
   * Returns a list of fields configured to be pre-filled with default values.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface[]
   *   The list of fields that should get pre-filled with default values.
   */
  public function getDefaultPropertyFields();

}
