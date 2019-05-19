<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalTypeInterface.
 */

namespace Drupal\temporal;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Temporal type entities.
 */
interface TemporalTypeInterface extends ConfigEntityInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * @return mixed
   */
  public function getFieldToTrack();

  /**
   * @param string $field_to_track
   */
  public function setFieldToTrack($field_to_track);
  
}
