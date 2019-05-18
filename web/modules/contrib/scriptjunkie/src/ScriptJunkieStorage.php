<?php

namespace Drupal\scriptjunkie;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Database\Query\Condition;

/**
 * Provides a class for CRUD operations on scriptjunkie scripts.
 */
class ScriptJunkieStorage implements ScriptJunkieStorageInterface {

  /**
   * The table for the scriptjunkie storage.
   */
  const TABLE = 'scriptjunkie';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Path CRUD object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing Scripts.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function save($name, $general = '', $scope = '', $script = '', $roles = '', $pages = '', $sid = NULL) {

    $fields = array(
      'name' => $name,
      'general' => $general,
      'scope' => $scope,
      'script' => $script,
      'roles' => $roles,
      'pages' => $pages,
    );

    // Insert or update the script.
    if (empty($sid)) {
      try {
        $query = $this->connection->insert(static::TABLE)
          ->fields($fields);
        $sid = $query->execute();
      }
      catch (\Exception $e) {
        // Propagate the exception.
        throw $e;
      }

      $fields['sid'] = $sid;
      $operation = 'insert';
    }
    else {
      // Fetch the current values so that an update hook can identify what
      // exactly changed.
      try {
        $original = $this->connection->query('SELECT name, general, scope, script, roles, pages FROM {scriptjunkie} WHERE sid = :sid', array(':sid' => $sid))
          ->fetchAssoc();
      }
      catch (\Exception $e) {
        $this->catchException($e);
        $original = FALSE;
      }
      $fields['sid'] = $sid;
      $query = $this->connection->update(static::TABLE)
        ->fields($fields)
        ->condition('sid', $sid);
      $sid = $query->execute();
      $fields['original'] = $original;
      $operation = 'update';
    }
    if ($sid) {
      return $fields;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function load($conditions) {
    $select = $this->connection->select(static::TABLE);
    foreach ($conditions as $field => $value) {
      if ($field == 'name') {
        // Use LIKE for case-insensitive matching.
        $select->condition($field, $this->connection->escapeLike($value), 'LIKE');
      }
      else {
        $select->condition($field, $value);
      }
    }
    try {
      return $select
        ->fields(static::TABLE)
        ->orderBy('sid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchAssoc();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($conditions) {
    $query = $this->connection->delete(static::TABLE);
    foreach ($conditions as $field => $value) {
      if ($field == 'name') {
        // Use LIKE for case-insensitive matching.
        $query->condition($field, $this->connection->escapeLike($value), 'LIKE');
      }
      else {
        $query->condition($field, $value);
      }
    }
    try {
      $deleted = $query->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      $deleted = FALSE;
    }
    return $deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function scriptExists($name) {
    // Use LIKE and NOT LIKE for case-insensitive matching.
    $query = $this->connection->select(static::TABLE)
      ->condition('name', $this->connection->escapeLike($name), 'LIKE');
    $query->addExpression('1');
    $query->range(0, 1);
    try {
      return (bool) $query->execute()->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScriptJunkieSettings($conditions = array()) {
    $defaults = array(
      'name' => '',
      'general' => array(
        'title' => '',
        'description' => '',
      ),
      'scope' => 'footer',
      'script' => '',
      'roles' => array(
        'visibility' => array(),
      ),
      'pages' => array(
        'mode' => '0',
        'list' => implode("\n", array(
          '/admin',
          '/admin/*',
          '/user/*/*',
          '/node/add*',
          '/node/*/*',
        )),
      ),
    );

    if (!empty($conditions)) {

      $select = $this->connection->select(static::TABLE);

      foreach ($conditions as $field => $value) {
        if ($field == 'name') {
          // Use LIKE for case-insensitive matching.
          $select->condition($field, $this->connection->escapeLike($value), 'LIKE');
        }
        else {
          $select->condition($field, $value);
        }
      }

      $script = $select
        ->fields(static::TABLE)
        ->range(0, 1)
        ->execute()
        ->fetchAssoc();

      $serialized_values = array('general', 'roles', 'pages');
      foreach ($serialized_values as $key) {
        $script[$key] = unserialize($script[$key]);
      }
      return array_replace_recursive($defaults, $script);
    }
    else {
      return $defaults;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function scriptJunkieGetScripts($conditions = array(), $data = 'all') {
    $select = $this->connection->select(static::TABLE);
    $scripts = array();
    switch ($data) {
      case 'info':
        $results = $select->fields(static::TABLE, ['name', 'general'])->execute();
        while ($result = $results->fetchAssoc()) {
          $script['general'] = unserialize($result['general']);
          $scripts[$result['name']] = $script;
        }
        break;

      default:
        foreach ($conditions as $field => $value) {
          if ($field == 'name') {
            // Use LIKE for case-insensitive matching.
            $select->condition($field, $this->connection->escapeLike($value), 'LIKE');
          }
          else {
            $select->condition($field, $value);
          }
        }
        $results = $select->fields(static::TABLE)->execute();
        while ($script = $results->fetchAssoc()) {
          $script['general'] = unserialize($script['general']);
          $script['roles'] = unserialize($script['roles']);
          $script['pages'] = unserialize($script['pages']);
          $scripts[$script['name']] = $script;
        }
        break;
    }
    return $scripts;
  }

  /**
   * {@inheritdoc}
   */
  public function getScriptsForAdminListing($header, $keys = NULL) {
    $query = $this->connection->select(static::TABLE)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    try {
      return $query
        ->fields(static::TABLE)
        ->orderByHeader($header)
        ->limit(50)
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function scriptJunkieIsValidNamespace($name) {
    return preg_match('/^([a-z0-9_]+)$/', $name) > 0;
  }

}
