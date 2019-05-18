<?php

/**
 * @file
 * Contains Drupal\evercurrent\UpdateHelperInterface.
 */

namespace Drupal\evercurrent;

/**
 * Interface UpdateHelperInterface.
 *
 * @package Drupal\evercurrent
 */
interface UpdateHelperInterface {

  /**
   * @param bool $force_update
   * @return mixed
   */
  public function sendUpdates($force_update = TRUE);
}
