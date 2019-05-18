<?php

namespace Drupal\drupal_reset;

/**
 * Interface DeleteFilesInterface.
 *
 * @package Drupal\drupal_reset
 */
interface DeleteFilesInterface {

  /**
   * Delete all public and private files.
   */
  public function deletefiles();

}
