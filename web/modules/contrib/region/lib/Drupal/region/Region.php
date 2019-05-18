<?php

/**
 * @file
 * Definition of Drupal\region\Region.
 */

namespace Drupal\region;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the region entity.
 */
class Region extends ConfigEntityBase {

  /**
   * The region ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The region UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The region label.
   *
   * @var string
   */
  public $label;

}
