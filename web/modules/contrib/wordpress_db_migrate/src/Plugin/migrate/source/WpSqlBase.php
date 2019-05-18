<?php

namespace Drupal\wordpress_db_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Base class for source plugins for WordPress DB migrations.
 *
 * We don't really need anything explicit here but it is good to have it for future
 * proofing in case we need to add common helpers.
 */
abstract class WpSqlBase extends SqlBase {

  /**
   * Get the time for the time string.
   *
   * @param string $time_string
   *   The time in a compatible format.
   * @return int
   *   The timestamp.
   */
  protected function strToTime($time_string) {
    return strtotime($time_string);
  }

  /**
   * Get the time for the time string in UTC.
   *
   * @param string $time_string
   *   The time in a compatible format.
   * @return int
   *   The timestamp.
   */
  protected function strToTimeUtc($time_string) {
    return strtotime($time_string . ' UTC');
  }

  /**
   * Get all the meta values of an object (post, comment, user, term).
   *
   * @param string $meta_table
   *   The name of the table with the meta information.
   * @param string $object_id_col_name
   *   The name of the column containing object id.
   * @param mixed $object_id
   *   The object id to look for.
   *
   * @return array
   *   Associative array containing all meta information for $object_id
   *   keyed with meta keys.
   */
  protected function getMetaValues($meta_table, $object_id_col_name, $object_id) {
    $query = $this->select($meta_table, 'meta');
    $query->fields('meta', ['meta_key', 'meta_value']);
    $query->condition('meta.' . $object_id_col_name, $object_id);

    return $query->execute()
      ->fetchAllKeyed();
  }

}
