<?php

namespace Drupal\dropshark\Queue;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\dropshark\Collector\CollectorInterface;
use Drupal\dropshark\Request\RequestInterface;

/**
 * Class DbQueue.
 */
class DbQueue implements QueueInterface {

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The lock currently in use.
   *
   * @var string
   */
  protected $currentLock;

  /**
   * Data collected throughout the request.
   *
   * @var array
   */
  protected $data = [];

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Deferred collectors.
   *
   * @var \Drupal\dropshark\Collector\CollectorInterface[]
   */
  protected $deferred = [];

  /**
   * Indicates that the queue should transmit during the current HTTP request.
   *
   * @var bool
   */
  protected $immediateTransmit = FALSE;

  /**
   * Request handler.
   *
   * @var \Drupal\dropshark\Request\RequestInterface
   */
  protected $request;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * DbQueue constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database.
   * @param \Drupal\dropshark\Request\RequestInterface $request
   *   Request handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration options.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(Connection $db, RequestInterface $request, ConfigFactoryInterface $configFactory, StateInterface $state) {
    $this->db = $db;
    $this->request = $request;
    $this->config = $configFactory->get('dropshark.settings');
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function add(array $item) {
    $item['ds_timestamp'] = $data['created'] = $this->timestamp();
    $data['data'] = $item;
    $this->data[] = $data;
    dropshark_set_shutdown_function();
  }

  /**
   * Clear expired locks from queue items.
   *
   * @param int $timestamp
   *   The timestamp for which to check lock expiration, defaults to current
   *   time minus the lock expiration configuration.
   */
  public function clearLocks($timestamp = NULL) {
    if (!$timestamp) {
      $timestamp = $this->timestamp() - $this->config->get('queue.lock_max');
    }
    $query = 'UPDATE {dropshark_queue} SET lock_id = NULL , lock_time = NULL WHERE lock_time < ?';
    $this->db->query($query, [$timestamp]);
  }

  /**
   * Gets items from persistent storage.
   *
   * @return array
   *   Items obtained from persistent storage.
   */
  protected function getItems() {
    // Lock the next X items.
    $this->currentLock = $this->lock();

    $query = 'SELECT data FROM {dropshark_queue} WHERE lock_id = ? ORDER BY created';

    $data = [];
    foreach ($this->db->query($query, [$this->currentLock]) as $item) {
      $data[] = [
        'type' => 'persistent',
        'data' => json_decode($item->data),
      ];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDeferred() {
    return !empty($this->deferred);
  }

  /**
   * Lock a set of items to prevent duplicate processing.
   *
   * @return string
   *   The key of the locked items.
   */
  protected function lock() {
    mt_srand(time() / __LINE__);
    $key = md5(__METHOD__ . microtime() . mt_rand(0, 999999));

    $query = 'UPDATE {dropshark_queue} SET lock_id = ? , lock_time = ? WHERE lock_id IS NULL ORDER BY CREATED';
    $this->db->query($query, [$key, $this->timestamp()]);

    return $key;
  }

  /**
   * {@inheritdoc}
   */
  public function needsImmediateTransmit() {
    return $this->immediateTransmit;
  }

  /**
   * {@inheritdoc}
   */
  public function persist() {
    // @TODO: write these in a batch or batches.
    foreach ($this->data as $item) {
      $this->db->insert('dropshark_queue')
        ->fields([
          'created' => $item['created'],
          'data' => json_encode($item['data']),
        ])->execute();
    }
    $this->data = [];
  }

  /**
   * {@inheritdoc}
   */
  public function processDeferred() {
    foreach ($this->deferred as $collector) {
      $collector->finalize();
    }
    $this->deferred = [];
  }

  /**
   * Removes items from the queue by lock.
   */
  protected function removeItems() {
    if (!$this->currentLock) {
      return;
    }

    $query = 'DELETE FROM {dropshark_queue} WHERE lock_id = ?';
    $this->db->query($query, [$this->currentLock]);
    $this->currentLock = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeferred(CollectorInterface $collector) {
    $this->deferred[] = $collector;
  }

  /**
   * {@inheritdoc}
   */
  public function setImmediateTransmit() {
    $this->immediateTransmit = TRUE;
  }

  /**
   * Provides a timestamp for queue entries.
   *
   * @return int
   *   Unix type timestamp of when the queue item was added.
   */
  protected function timestamp() {
    return time();
  }

  /**
   * {@inheritdoc}
   */
  public function transmit() {
    // Clear any old stuff that didn't finish processing.
    $this->clearLocks();

    // Get persisted items, merge with static items.
    $items = array_merge($this->getItems(), $this->data);
    $data = [];
    foreach ($items as $item) {
      $data[] = $item['data'];
    }

    // Attempt to transmit.
    $result = $this->transmitItems($data);
    if ($result->code != 200) {
      // Handle error.
      $this->unlock();
      $this->persist();
    }
    else {
      // On success clear data from queue.
      $this->removeItems();
      $this->data = [];
    }

    $this->deferred = [];
    $this->immediateTransmit = FALSE;
  }

  /**
   * Process queued items.
   *
   * @param array $items
   *   Data to be transmitted.
   *
   * @return object
   *   The response object.
   */
  protected function transmitItems(array $items) {
    $params['data'] = json_encode($items);
    $params['site_id'] = $this->state->get('dropshark.site_id');
    return $this->request->postData($params);
  }

  /**
   * Unlock queued items.
   */
  protected function unlock() {
    if (!$this->currentLock) {
      return;
    }

    $query = 'UPDATE {dropshark_queue} SET lock_id = NULL WHERE lock_id = ?';
    $this->db->query($query, [$this->currentLock]);
    $this->currentLock = NULL;
  }

}
