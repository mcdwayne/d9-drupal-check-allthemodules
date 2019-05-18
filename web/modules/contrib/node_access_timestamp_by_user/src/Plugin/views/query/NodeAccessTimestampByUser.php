<?php

namespace Drupal\node_access_timestamp_by_user\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * Node access timestamp by user views plugin.
 *
 * @ViewsQuery(
 *   id = "node-access-timestamp-by-user",
 *   title = @Translation("Node Access Timestamp by User"),
 *   help = @Translation("Node Access Timestamp by User")
 * )
 */
class NodeAccessTimestampByUser extends QueryPluginBase {

  /**
   * @{inheritdoc}
   */
  public function ensureTable($table, $relationship = NULL) {
    return '';
  }
  
  /**
   * @{inheritdoc}
   */
  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

}
