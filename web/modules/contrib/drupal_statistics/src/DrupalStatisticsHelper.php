<?php

namespace Drupal\drupal_statistics;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

/**
 * Helper class to get data for user and node blocks.
 */
class DrupalStatisticsHelper {

  private $user;

  /**
   * Function to create object of storage helper class.
   */
  public static function instance() {
    static $inst = NULL;
    if ($inst === NULL) {
      $inst = new DrupalStatisticsHelper();
    }
    return $inst;
  }

  /**
   * Constructor of storage helper class.
   */
  public function __construct() {
    $this->user = User::load(\Drupal::currentUser()->id());
  }

  /**
   * This function returns the read/not-read flag for the nodes.
   */
  public function getStatistics() {
    $nids = \Drupal::entityQuery('node')->execute();
    $read_flag = history_read_multiple($nids);
    foreach ($read_flag as $nid => $timestamp) {
      if ($timestamp == 0) {
        $data[$nid] = "not read";
      }
      else {
        $data[$nid] = "read";
      }
    }
    return $data;
  }

  /**
   * This function returns the number of nodes read by the user.
   */
  public function getStatisticsCount() {
    $nids = \Drupal::entityQuery('node')->execute();
    $read_flag = history_read_multiple($nids);
    $count = 0;
    foreach ($read_flag as $value) {
      if ($value != 0) {
        $count++;
      }
    }
    return $count;
  }

  /**
   * This function returns the number of visits on a node.
   */
  public function getNodeVisitCount() {
    $count = -1;
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      $count++;
      $database = Database::getConnection();
      $count += (int) $database->query("SELECT count(uid) as count FROM {history} where nid=" . $node->id())->fetchAssoc()['count'];
    }
    return $count;
  }

  /**
   * This function returns the joining date of a user.
   */
  public function getJoinDate() {
    return $this->user->getCreatedTime();
  }

  /**
   * This function returns the joining date of a user.
   */
  public function getUserLastLoginTime() {
    return $this->user->getLastLoginTime();
  }

}
