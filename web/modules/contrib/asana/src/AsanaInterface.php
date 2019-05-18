<?php

namespace Drupal\asana;

/**
 * Interface AsanaInterface.
 *
 * @package Drupal\asana
 */
interface AsanaInterface {

  /**
   * Returns a project objects array.
   *
   * @return array
   *   The project array.
   */
  public function getAllProjects();

}
