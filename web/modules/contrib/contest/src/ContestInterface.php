<?php

namespace Drupal\contest;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining an contest entity.
 */
interface ContestInterface extends ContentEntityInterface {

  /**
   * Get the current user's ID.
   *
   * @return array
   *   A one element array with the current user's uid.
   */
  public static function getCurrentUserId();

  /**
   * Sorting function for Contest entities.
   *
   * @param object $a
   *   A contest object.
   * @param object $b
   *   A contest object.
   *
   * @return int
   *   An integer, (1, 0, -1) used to sort the contest entities.
   */
  public static function sort($a, $b);

}
