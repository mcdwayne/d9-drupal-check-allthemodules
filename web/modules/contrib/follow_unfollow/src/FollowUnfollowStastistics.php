<?php

namespace Drupal\follow_unfollow;

use Drupal\Core\Database\Database;

/**
 * Defines the access control handler for the block entity type.
 *
 * @see \Drupal\follow_unfollow\Form\FollowUnfollowForm
 */
class FollowUnfollowStastistics {
  /**
   * The result variable.
   *
   * @var \Drupal\follow_unfollow\FollowUnfollowStatistics
   */
  protected $result;

  /**
   * Constructs the FollowUnfollowStatistics.
   */
  public function __construct() {
    $this->result = FALSE;
  }

  /**
   * Determines statistics.
   *
   * @param int $nid
   *   The node id.
   * @param int $tid
   *   The taxonomy id.
   * @param int $targetUid
   *   The target user uid.
   * @param int $authorUid
   *   The author user uid.
   * @param bool $status
   *   The status.
   *
   * @return int
   *   The count of statistics.
   */
  public function statistics($nid, $tid, $targetUid, $authorUid, $status) {

    // Retrieves a \Drupal\Core\Database\Connection which is a PDO instance.
    $connection = Database::getConnection();

    $query = $connection->select('follow_unfollow_statistics', 'fs');

    $query->fields('fs', ['nid']);
    $query->addExpression('COUNT(fs.nid)', 'ncount');

    if (!empty($nid)) {
      $query->condition('nid', $nid, '=');
      $query->groupBy('fs.nid');
    }

    if (!empty($tid)) {
      $query->condition('tid', $tid, '=');
      $query->groupBy('fs.tid');
    }

    if (!empty($targetUid)) {
      $query->condition('uid', $targetUid, '=');
      $query->groupBy('fs.uid');
    }

    if (!empty($authorUid)) {
      $query->condition('author_uid', $authorUid, '=');
    }

    $query->condition('status', $status, '=');
    $result = $query->execute()->fetchObject();

    return $result;
  }

}
