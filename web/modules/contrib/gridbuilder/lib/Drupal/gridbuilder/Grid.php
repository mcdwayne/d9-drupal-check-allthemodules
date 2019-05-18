<?php

/**
 * @file
 * Definition of Drupal\gridbuilder\Grid.
 */

namespace Drupal\gridbuilder;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the grid entity.
 */
class Grid extends ConfigEntityBase {

  /**
   * The grid ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The grid UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The grid label.
   *
   * @var string
   */
  public $label;

  /**
   * The grid type: whether the grid is fixed (0) or fluid (1).
   *
   * @var int
   */
  public $type;

  /**
   * Width of grid in pixels (or 100 for fluid grids).
   *
   * @var int
   */
  public $width;

  /**
   * Number of columns in this grid.
   *
   * @var int
   */
  public $columns;

  /**
   * Column padding value. Understood in pixels for fixed grids (eg. 10) or as percentage for fluid grids (eg. 1.5).
   *
   * @var int
   */
  public $padding_width;

  /**
   * Gutter width value. Understood in pixels for fixed grids (eg. 20) or as percentage for fluid grids (eg. 2).
   *
   * @var int
   */
  public $gutter_width;

  /**
   * List of breakpoint machine names that this grid will apply to.
   *
   * @var array
   */
  public $breakpoints;

}
