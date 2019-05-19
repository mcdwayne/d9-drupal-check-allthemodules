<?php

/**
 * @file
 * Contains \Drupal\smartling\SubmissionStorageInterface.
*/

namespace Drupal\smartling;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an interface for smartling_submission entity storage classes.
 */
interface SubmissionStorageInterface extends EntityStorageInterface {

  /**
   * Returns submission IDs that needs invalidation of status.
   *
   * @return int[]
   *   A list of feed IDs that needs status checking.
   */
  public function getSubmissionsIdsToCheckStatus();

}
