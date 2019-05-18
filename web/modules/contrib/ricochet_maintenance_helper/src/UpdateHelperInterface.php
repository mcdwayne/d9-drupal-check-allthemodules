<?php

/**
 * @file
 * Contains Drupal\ricochet_maintenance_helper\UpdateHelperInterface.
 */

namespace Drupal\ricochet_maintenance_helper;

/**
 * Interface UpdateHelperInterface.
 *
 * @package Drupal\ricochet_maintenance_helper
 */
interface UpdateHelperInterface {

  /**
   * @param bool $force_update
   * @return mixed
   */
  public function sendUpdates($force_update = TRUE);
}
