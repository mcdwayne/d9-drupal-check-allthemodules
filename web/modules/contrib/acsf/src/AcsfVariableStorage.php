<?php

namespace Drupal\acsf;

use Drupal\Core\Database\Connection;

/**
 * Encapsulates functionality to interact with ACSF variables.
 */
class AcsfVariableStorage {

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The active database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Sets a named variable with an optional group.
   *
   * @param string $name
   *   The name of the variable.
   * @param mixed $value
   *   The value of the variable.
   * @param string $group
   *   The group name of the variable. Optional.
   *
   * @return int
   *   1 if an INSERT query was executed, 2 for UPDATE.
   */
  public function set($name, $value, $group = NULL) {

    return $this->connection->merge('acsf_variables')
      ->keys(['name' => $name])
      ->fields([
        'group_name' => $group,
        'value' => serialize($value),
      ])
      ->execute();
  }

  /**
   * Retrieves a named variable.
   *
   * @param string $name
   *   The name of the variable.
   * @param mixed $default
   *   The default value of the variable.
   *
   * @return mixed
   *   The value of the variable.
   */
  public function get($name, $default = NULL) {

    $record = $this->connection->select('acsf_variables', 'v')
      ->fields('v', ['value'])
      ->condition('name', $name, '=')
      ->execute()
      ->fetchAssoc();

    if ($record) {
      return unserialize($record['value']);
    }
    else {
      return $default;
    }
  }

  /**
   * Retrieves variables whose names match a substring.
   *
   * @param string $match
   *   A substring that must occur in the variable name.
   *
   * @return array
   *   An associative array holding the values of the variables, keyed by the
   *   variable names.
   */
  public function getMatch($match) {
    $return = [];

    $result = $this->connection->select('acsf_variables', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', '%' . $match . '%', 'LIKE')
      ->execute();

    while ($record = $result->fetchAssoc()) {
      $return[$record['name']] = unserialize($record['value']);
    }

    return $return;
  }

  /**
   * Retrieves a group of variables.
   *
   * @param string $group
   *   The group name of the variables.
   * @param mixed $default
   *   The default value of the group.
   *
   * @return array
   *   An associative array holding the values of the group of variables, keyed
   *   by the variable names.
   */
  public function getGroup($group, $default = []) {
    $return = [];

    $result = $this->connection->select('acsf_variables', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('group_name', $group, '=')
      ->execute();

    while ($record = $result->fetchAssoc()) {
      $return[$record['name']] = unserialize($record['value']);
    }

    if (empty($return)) {
      return $default;
    }
    else {
      return $return;
    }
  }

  /**
   * Deletes a named variable.
   *
   * @param string $name
   *   The name of the variable.
   *
   * @return int
   *   The number of deleted rows.
   */
  public function delete($name) {
    $result = $this->connection->delete('acsf_variables')
      ->condition('name', $name)
      ->execute();

    return $result;
  }

}
