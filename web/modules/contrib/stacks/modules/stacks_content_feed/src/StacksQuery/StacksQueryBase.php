<?php
/**
 * @file
 * Contains \Drupal\stacks_content_feed\StacksQuery\StacksQueryBase
 */

namespace Drupal\stacks_content_feed\StacksQuery;

/**
 * Class StacksQueryBase
 */
abstract class StacksQueryBase {

  protected $unique_id;

  /**
   * Queries the database for nodes.
   * @param $options
   * @return mixed
   */
  abstract public function getNodeResults($options);

  /**
   * Handles the default sorting options for the database query.
   * @param $query
   * @param $order_by
   */
  protected function getNodeResultsSort(&$query, $order_by) {
    if (preg_match('/(.*)_(asc|desc)/', $order_by, $matches)) {
      $query->sort($matches[1], strtoupper($matches[2]));
    }
  }

}
