<?php

namespace Drupal\riddle_marketplace;

/**
 * Interface RiddleFeedServiceInterface.
 *
 * @package Drupal\riddle_marketplace
 */
interface RiddleFeedServiceInterface {

  /**
   * Return feed for configured Riddle Account (Token).
   *
   * @return array
   *   Return feed from Riddle API Service or NULL if feed is not available.
   */
  public function getFeed();

}
