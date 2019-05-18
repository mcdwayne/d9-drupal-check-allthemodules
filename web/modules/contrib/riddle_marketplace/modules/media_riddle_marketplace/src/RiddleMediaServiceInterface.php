<?php

namespace Drupal\media_riddle_marketplace;

/**
 * Interface RiddleMediaServiceInterface.
 *
 * @package Drupal\media_riddle_marketplace
 */
interface RiddleMediaServiceInterface {

  /**
   * Creates all new riddles, for every riddle bundle.
   */
  public function createMediaEntities();

  /**
   * Checks all bundles which riddles exists and returns a list of missing ones.
   *
   * @return array
   *   List of bundle => riddles.
   */
  public function getNewRiddles();

}
