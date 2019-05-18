<?php

/**
 * @file
 * Definition of Drupal\rlayout\RLayout.
 */

namespace Drupal\rlayout;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the layout entity.
 */
class RLayout extends ConfigEntityBase {

  /**
   * The layout ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The layout UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The layout label.
   *
   * @var string
   */
  public $label;

  /**
   * List of regions used in this layout.
   *
   * @var array
   */
  public $regions;

  /**
   * Region width overrides for different breakpoints.
   *
   * @var array
   */
  public $overrides;

}
