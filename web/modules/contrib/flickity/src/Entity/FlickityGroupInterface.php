<?php

/**
 * @file
 * Contains \Drupal\flickity\Entity\FlickityGroupInterface.
 */

namespace Drupal\flickity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Flickity settings groups.
 */
interface FlickityGroupInterface extends ConfigEntityInterface {
  public function getSettings();
}
