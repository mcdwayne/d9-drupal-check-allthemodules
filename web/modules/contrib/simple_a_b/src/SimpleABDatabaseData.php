<?php

namespace Drupal\simple_a_b;

use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Create connections the the simple a/b data database.
 */
class SimpleABDatabaseData implements SimpleABStorageInterface {


  protected $connection;

  protected $state;

  protected $requestStack;

  private $table = 'simple_a_b_data';

  private $tableJoin = 'simple_a_b_tests';

  private $dontMove = ['tid'];

  /**
   * SimpleABDatabaseData constructor.
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
  public function create($data) {

    $tid = -1;

    try {
      $data = $this->formatDataForUpload($data);

      // Try to add the data into the database.
      $tid = $this->connection->insert($this->table)->fields($data)->execute();

      // Return the created tid.
      return $tid;
    }
    catch (\Exception $e) {

      // If error log the exception.
      \Drupal::logger('simple_a_b')->error($e);

      // Return -1 tid.
      return $tid;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update($did, $data) {

    try {
      // Format the data for upload.
      $data = $this->formatDataForUpload($data);

      // Try to update based upon the tid.
      $update = $this->connection->update($this->table)
        ->fields($data)
        ->condition('did', $did, "=")
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
  public function remove($tid) {
    try {

      // Try to delete the test.
      $status = $this->connection->delete($this->table)
        ->condition('tid', $tid)
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
  public function fetch($tid) {
    $query = $this->connection->select($this->table, 'd');
    $query->fields('d', ['did', 'tid', 'data', 'conditions', 'settings']);
    $query->fields('t', [
      'tid',
      'name',
      'description',
      'enabled',
      'type',
      'eid',
    ]);
    $query->join($this->tableJoin, 't', 'd.tid=t.tid');
    $query->condition('d.tid', $tid, '=');
    $query->range(0, 1);
    $data = $query->execute();
    $results = $data->fetch();

    $results = $this->formatDataForDownload($results);

    return $results;
  }

  /**
   * Format the data for upload to the database.
   *
   * @param object $data
   *   Array of data.
   *
   * @return array
   *   New array with keys added in
   */
  private function formatDataForUpload($data) {
    $output = [];
    $output['data'] = [];
    $output['settings'] = [];
    $output['conditions'] = [];

    // Move all data from its keys, into data as a serialize data.
    foreach ($data as $key => $item) {
      if (!in_array($key, $this->dontMove)) {
        $output['data'][$key] = $item;
      }
      else {
        // Remember to keep everything else.
        $output[$key] = $item;
      }
    }

    // Serialise data arrays.
    $output['data'] = serialize($output['data']);
    $output['settings'] = serialize($output['settings']);
    $output['conditions'] = serialize($output['conditions']);

    // Return the new data.
    return $output;
  }

  /**
   * Formats the data for use on forms.
   *
   * @param object $data
   *   Object of data.
   *
   * @return mixed
   *   Unserialize & put back fields.
   */
  private function formatDataForDownload($data) {

    // Unserialize the content.
    $data->data = unserialize($data->data);
    $data->settings = unserialize($data->settings);
    $data->conditions = unserialize($data->conditions);

    // Loop thought all 'data' separating it all back out.
    foreach ($data->data as $key => $value) {
      $data->{$key} = $value;
    }

    // Unset the data output.
    unset($data->data);

    return $data;
  }

}
