<?php

namespace Drupal\data_fixtures\Interfaces;

/**
 * Interface Generator.
 *
 * @package Drupal\data_fixtures\Interfaces
 */
interface Generator {

  /**
   * Load all fixtures.
   */
  public function load();

  /**
   * Unload all fixtures and clean up any mess left behind.
   */
  public function unLoad();

}
