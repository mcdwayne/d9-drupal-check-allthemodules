<?php

namespace Drupal\civicrm_tools;

/**
 * Interface CiviCrmDatabaseInterface.
 */
interface CiviCrmDatabaseInterface {

  /**
   * Executes a query straight from the CiviCRM database.
   *
   * Sets the connection to the CiviCRM database, that
   * must be declared as $databases['civicrm']['default'] in settings.php.
   * Example:
   *    $databases['civicrm']['default'] = [
   *      'database' => 'my_civicrm',
   *      'username' => 'root',
   *      'password' => 'root',
   *      'prefix' => '',
   *      'host' => '127.0.0.1',
   *      'port' => '3306',
   *      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
   *      'driver' => 'mysql',
   *    ];
   *
   * @param string $query
   *   Example "SELECT * FROM {civicrm_contact} LIMIT 100;".
   * @param array $args
   *   An array of arguments for the prepared statement. If the prepared
   *   statement uses ? placeholders, this array must be an indexed array.
   *   If it contains named placeholders, it must be an associative array.
   *   Example [
   *     ':contact_id' => $contact_id,
   *     ':status' => 'Added',
   *   ].
   * @param array $options
   *   An associative array of options to control how the query is run. The
   *   given options will be merged with self::defaultOptions(). See the
   *   documentation for self::defaultOptions() for details.
   *   Typically, $options['return'] will be set by a default or by a query
   *   builder, and should not be set by a user.
   *
   * @return array
   *   List of rows.
   */
  public function execute($query, array $args = [], array $options = []);

}
