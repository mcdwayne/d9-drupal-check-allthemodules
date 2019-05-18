<?php

/**
 * Encapsulates functionality to interact with ACSF variables.
 */
class AcsfVariableStorageMock {

  protected $storage;

  protected $group;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->storage = [];
    $this->group = [];
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
    $response = 1;
    if (isset($this->storage[$name])) {
      $response = 2;
    }
    $this->storage[$name] = [
      'group_name' => $group,
      'value' => serialize($value),
    ];
    $this->group[$group][] = $name;

    return $response;
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
    if (isset($this->storage[$name])) {
      return unserialize($this->storage[$name]['value']);
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

    if (isset($this->group[$group])) {
      foreach ($this->group[$group] as $name) {
        $return[$name] = unserialize($this->storage[$name]['value']);
      }
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
