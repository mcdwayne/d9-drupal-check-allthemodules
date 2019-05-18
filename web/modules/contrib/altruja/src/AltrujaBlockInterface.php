<?php

namespace Drupal\altruja;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an altruja block entity.
 */
interface AltrujaBlockInterface extends ConfigEntityInterface {

  /**
   * Returns the block id.
   */
  public function getId();

  /**
   * Returns the block title.
   */
  public function getLabel();

  /**
   * Returns the block code.
   */
  public function getCode();

}
