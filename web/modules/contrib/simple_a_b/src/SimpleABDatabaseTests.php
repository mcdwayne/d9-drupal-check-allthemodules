<?php

namespace Drupal\simple_a_b;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Makes changes to the simple a/b tests table.
 */
class SimpleABDatabaseTests implements SimpleABStorageInterface {

  protected $connection;

  protected $state;

  protected $requestStack;

  private $table = 'simple_a_b_tests';

  private $viewCache = 'config:views.view.simple_a_b_tests';

  /**
   * SimpleABDatabaseTests constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Connection.
   * @param \Drupal\Core\State\StateInterface $state
   *   State.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request_stack.
   */
  public function __construct(Connection $connection, StateInterface $state, RequestStack $request_stack) {
    $this->connection = $connection;
    $this->state = $state;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function create($test_data = [], $data_data = []) {

    $user = \Drupal::currentUser();
    $tid = -1;

    // Add in the created/updated user & timestamp.
    $test_data['created_by'] = $user->id();
    $test_data['created'] = \Drupal::time()->getRequestTime();
    $test_data['updated_by'] = $user->id();
    $test_data['updated'] = \Drupal::time()->getRequestTime();

    try {
      // Try to add the data into the database.
      $tid = $this->connection->insert($this->table)
        ->fields($test_data)
        ->execute();

      // Log that a new test has been created.
      \Drupal::logger('simple_a_b')
        ->info('New test "@name" (@tid) has been created', [
          '@name' => $test_data['name'],
          '@tid' => $tid,
        ]);

      // Invalidate the views cache
      // so that the view will show that something has been added.
      Cache::invalidateTags([$this->viewCache]);

      // Set the tid.
      $data_data['tid'] = $tid;

      // Update the data from data table.
      \Drupal::service('simple_a_b.storage.data')->create($data_data);

      // Update the simple a/b config
      // this helps to keep track of all the enabled / disabled modules.
      $this->updateConfig($test_data['type'], $tid, $test_data['eid'], $test_data['enabled']);

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
  public function update($tid, $did, $test_data = [], $data_data = []) {

    // Get current user.
    $user = \Drupal::currentUser();

    // Set the updated user & timestamp.
    $test_data['updated_by'] = $user->id();
    $test_data['updated'] = \Drupal::time()->getRequestTime();

    try {
      // Try to update based upon the tid.
      $update = $this->connection->update($this->table)
        ->fields($test_data)
        ->condition('tid', $tid, "=")
        ->execute();

      // Log that a new test has been updated.
      \Drupal::logger('simple_a_b')
        ->info('Test "@name" (@tid) has been updated', [
          '@name' => $test_data['name'],
          '@tid' => $tid,
        ]);

      // Invalidate the views cache
      // so that the view will show that something has been updated.
      Cache::invalidateTags([$this->viewCache]);

      // Update the data from data table.
      \Drupal::service('simple_a_b.storage.data')->update($did, $data_data);

      // Update the simple a/b config
      // this helps to keep track of all the enabled / disabled modules.
      $this->updateConfig($test_data['type'], $tid, $test_data['eid'], $test_data['enabled']);

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

      // Fetch the data so we can update the config data
      // this helps to keep track of all the enabled / disabled modules
      // in this case it will always make sure we
      // remove the deleted data from the config.
      $test = $this->fetch($tid);
      $this->updateConfig($test->type, $tid, $test->eid, FALSE);

      // Try to delete the test.
      $status = $this->connection->delete($this->table)
        ->condition('tid', $tid)
        ->execute();

      // Log that we have deleted a test.
      \Drupal::logger('simple_a_b')
        ->info('Test "@tid" has been removed', [
          '@tid' => $tid,
        ]);

      // Invalidate the views cache
      // so that the view will show that something has been removed.
      Cache::invalidateTags([$this->viewCache]);

      // Remove the data from data table.
      \Drupal::service('simple_a_b.storage.data')->remove($tid);

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

    $test = $this->connection->select($this->table, 't')
      ->fields('t', ['tid', 'name', 'description', 'enabled', 'type', 'eid'])
      ->condition('t.tid', $tid, '=')
      ->range(0, 1)
      ->execute()->fetch();

    return $test;
  }

  /**
   * Updates the config.
   *
   * @param string $name
   *   Name of the test.
   * @param int $tid
   *   Tid of the test.
   * @param int $eid
   *   Eid of the test.
   * @param bool $enabled
   *   Enabled state of the test.
   */
  private function updateConfig($name, $tid, $eid, $enabled) {

    // Create a new array with the new data.
    $eids = [$tid => $eid];

    // Try to load the config data.
    $config = \Drupal::service('simple_a_b.storage.config')->fetch($name);

    // Join the new eid to the old config
    // or create an new array with just the new eid.
    $data = $config ? $config->data + $eids : $eids;

    // If the eid is not enabled
    // make sure we remove it from the list.
    if (!$enabled) {
      unset($data[$tid]);
    }

    // If we don't have any config
    // we can assume one does not exist therefore we should create one
    // otherwise we can update the existing config.
    if (!$config) {
      \Drupal::service('simple_a_b.storage.config')->create($name, $data);
    }
    else {
      \Drupal::service('simple_a_b.storage.config')->update($name, $data);
    }

  }

}
