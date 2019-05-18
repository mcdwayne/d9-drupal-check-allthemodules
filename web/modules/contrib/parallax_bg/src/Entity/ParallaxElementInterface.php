<?php

namespace Drupal\parallax_bg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Parallax element entities.
 */
interface ParallaxElementInterface extends ConfigEntityInterface {

  /**
   * @return string
   */
  public function getSelector();

  /**
   * @return string
   */
  public function getPosition();

  /**
   * @return string
   */
  public function getSpeed();

}
