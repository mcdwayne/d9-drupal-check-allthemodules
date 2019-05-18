<?php
/**
* @file providing the service that say custom table explanation 'queryresult'.
*
*/
namespace  Drupal\custom_db_table_views;

use Drupal\Core\Database;

class CustomDatabaseService {

/**
 * Set database connection.
 */
  public function custom_db_table($tablename) {
    $results = db_query("show columns FROM $tablename")->fetchAll();
    return $results;
  }

  /**
   * Set primary key in query.
   */
  public function custom_db_table_primary_key($tablename) {
    $results = db_query("SHOW KEYS FROM $tablename WHERE Key_name = 'PRIMARY'")->fetchAll();
    return $results;
  }

 }
