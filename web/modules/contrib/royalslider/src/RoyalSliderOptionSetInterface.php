<?php
/**
 * @file
 * Contains \Drupal\royalslider\RoyalSliderOptionSet.
 */

namespace Drupal\royalslider;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a RoyalSliderOptionSet entity type.
 */
interface RoyalSliderOptionSetInterface extends ConfigEntityInterface {
  public function buildJsOptionset();
}