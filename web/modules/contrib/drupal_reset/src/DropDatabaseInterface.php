<?php

namespace Drupal\drupal_reset;

/**
 * Interface DropDatabaseInterface.
 *
 * @package Drupal\drupal_reset
 */
interface DropDatabaseInterface {

  /**
   * Delete all database tables.
   */
  public function dropdatabase();

   /**
    * Check if the installation uses a single-database and a simple prefix.
    */
  public function validateIsSupported();

}

