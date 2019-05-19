<?php

namespace Drupal\simple_a_b;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Create connections the the simple a/b config database.
 */
class SimpleABDatabaseConfig implements SimpleABStorageInterface {


  protected $connection;

  protected $state;

  protected $requestStack;

  private $table = 'simple_a_b_config';

  /**
   * SimpleABDatabaseConfig constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Connections.
   * @param \Drupal\Core\State\StateInterface $state
   *   State.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   */
  public function __construct(Connection $connection, StateInterface $state, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->state = $state;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function create($name = "", $data = []) {
    $key = "";
    $input = [];
    $input['name'] = $name;
    $input['data'] = serialize($data);

    // Try to add the data into the database.
    try {
      $key = $this->connection->insert($this->table)
        ->fields($input)
        ->execute();
      return $key;
    }
    catch (\Exception $e) {

      // If error log the exception.
      \Drupal::logger('simple_a_b')->error($e);

      // Return -1 tid/.
      return $key;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update($name, $data) {

    try {
      $input = [];
      $input['data'] = serialize($data);

      // Try to update based upon the name.
      $update = $this->connection->update($this->table)
        ->fields($input)
        ->condition('name', $name, "=")
        ->execute();

      // Return the status.
      return $update;
    }
    catch (\Exception $e) {
      // If error log the exception.
      \Drupal::logger('simple_a_b')->error($e);

      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function remove($name) {
    try {
      // Try to delete the config data.
      $status = $this->connection->delete($this->table)
        ->condition('name', $name)
        ->execute();

      // Return the status.
      return $status;
    }
    catch (\Exception $e) {
      // If error log the exception.
      \Drupal::logger('simple_a_b')->error($e);

      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($name) {
    $query = $this->connection->select($this->table, 'c');
    $query->fields('c', ['name', 'data']);
    $query->condition('c.name', $name, '=');
    $query->range(0, 1);

    $data = $query->execute();
    $results = $data->fetch();

    $results = $this->formatDataForDownload($results);

    return $results;
  }

  /**
   * Formats the data for use on forms.
   *
   * @param object $data
   *   Data to be unserialize.
   *
   * @return mixed
   *   returns unserialize data
   */
  private function formatDataForDownload($data) {

    if (isset($data->data)) {
      $data->data = unserialize($data->data);
    }

    return $data;
  }

}
