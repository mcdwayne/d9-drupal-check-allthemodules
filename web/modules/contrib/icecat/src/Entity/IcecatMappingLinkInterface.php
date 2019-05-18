<?php

namespace Drupal\icecat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface IcecatMappingLinkInterface.
 *
 * @package Drupal\icecat\Entity
 */
interface IcecatMappingLinkInterface extends ConfigEntityInterface {

  /**
   * Gets the local field.
   */
  public function getLocalField();

  /**
   * Gets the remote field.
   */
  public function getRemoteField();

  /**
   * Gets the parent mapping.
   */
  public function getMapping();

}
