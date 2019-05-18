<?php

namespace Drupal\openstack_queues\Queue;

use Drupal;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Queue\QueueInterface;
use OpenCloud\Rackspace;
use OpenCloud\Queues\Service;
use OpenCloud\Queues\Resource\Queue;
use OpenCloud\Common\Collection\PaginatedIterator;
use OpenCloud\Queues\Resource\Message;
use stdClass;

class OpenstackQueue implements QueueInterface {

  /**
   * @var ImmutableConfig $config
   */
  private $config;
  /**
   * @var string $name
   */
  private $name;
  /**
   * @var Rackspace $connection
   */
  private $connection;
  /**
   * @var Service $service
   */
  private $service;
  /**
   * @var Queue $queue
   */
  private $queue;

  public function __construct($name, Rackspace $connection, ImmutableConfig $config) {
    $this->config = $config;
    $this->connection = $connection;
    $this->setName($name);
    $this->connect();
  }

  /**
   * Adds a queue item and store it directly to the queue.
   *
   * @param $data
   *   Arbitrary data to be associated with the new task in the queue.
   *
   * @return bool
   *   TRUE or PaginatedIterator if the item was successfully created and was
   *   (best effort) added to the queue, otherwise FALSE. We don't guarantee the
   *   item was committed to disk etc, but as far as we know, the item is now in
   *   the queue.
   */
  public function createItem($data) {
    $ttl = ($this->config->get('ttl')) ? $this->config->get('ttl') : 3600;
    return $this->queue->createMessage(array(
      'body' => json_encode($data),
      'ttl' => $ttl,
    ));
  }

  /**
   * Retrieves the number of items in the queue.
   *
   * This is intended to provide a "best guess" count of the number of items in
   * the queue. Depending on the implementation and the setup, the accuracy of
   * the results of this function may vary.
   *
   * e.g. On a busy system with a large number of consumers and items, the
   * result might only be valid for a fraction of a second and not provide an
   * accurate representation.
   *
   * @return int
   *   An integer estimate of the number of items in the queue.
   */
  public function numberOfItems() {
    $stats = $this->queue->getStats();
    return ($stats) ? $stats->total : 0;
  }

  /**
   * Claims an item in the queue for processing.
   *
   * @param $lease_time int
   *   How long the processing is expected to take in seconds, defaults to an
   *   hour. After this lease expires, the item will be reset and another
   *   consumer can claim the item. For idempotent tasks (which can be run
   *   multiple times without side effects), shorter lease times would result
   *   in lower latency in case a consumer fails. For tasks that should not be
   *   run more than once (non-idempotent), a larger lease time will make it
   *   more rare for a given task to run multiple times in cases of failure,
   *   at the cost of higher latency. Value must be between 60 and 43200
   *   seconds. Default is 12 hours.
   * @param $grace_period int
   *   The server extends the lifetime of claimed messages at least as long as
   *   the claim itself, plus a specified grace period to deal with crashed
   *   workers. Value must be between 60 and 43200 seconds. Default is 12 hours.
   *
   * @return stdClass | bool
   *   On success we return an item object. If the queue is unable to claim an
   *   item it returns false. This implies a best effort to retrieve an item
   *   and either the queue is empty or there is some other non-recoverable
   *   problem.
   *
   *   If returned, the object will have at least the following properties:
   *   - data: the same as what what passed into createItem().
   *   - item_id: the unique ID returned from createItem().
   *   - created: timestamp when the item was put into the queue.
   */
  public function claimItem($lease_time = 43200, $grace_period = 43200) {
    $options = array();
    $options['ttl'] = $lease_time;
    $options['grace'] = $grace_period;

    // Drupal claims items one at a time.
    $options['limit'] = 1;

    $item = new stdClass();

    /** @var PaginatedIterator $messages */
    if ($messages = $this->queue->claimMessages($options)) {
      /** @var Message $message */
      foreach($messages as $message) {
        $item->item_id = $message->getId();
        if (!empty($item->item_id)) {
          $item->data = json_decode($message->getBody(), TRUE);
          return $item;
        }
      }
    }

    return FALSE;
  }

  /**
   * Deletes a finished item from the queue.
   *
   * @param $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   */
  public function deleteItem($item) {
    $this->queue->deleteMessages(array($item->item_id));
  }

  /**
   * Releases an item that the worker could not process.
   *
   * Another worker can come in and process it before the timeout expires.
   *
   * @param $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   *
   * @return bool
   *   TRUE if the item has been released, FALSE otherwise.
   */
  public function releaseItem($item) {
    $claimed_item = $this->queue->getClaim($item->item_id);
    if ($claimed_item) {
      $response = $claimed_item->delete();
      return $response->isSuccessful();
    }

    return FALSE;
  }

  /**
   * Creates a queue.
   *
   * Called during installation and should be used to perform any necessary
   * initialization operations. This should not be confused with the
   * constructor for these objects, which is called every time an object is
   * instantiated to operate on a queue. This operation is only needed the
   * first time a given queue is going to be initialized (for example, to make
   * a new database table or directory to hold tasks for the queue -- it
   * depends on the queue implementation if this is necessary at all).
   */
  public function createQueue() {
    $this->queue = $this->service->createQueue($this->name);
  }

  /**
   * Deletes a queue and every item in the queue.
   */
  public function deleteQueue() {
    $this->queue = $this->service->getQueue();
    $this->queue->setName($this->name);
    $this->queue->delete();
  }

  private function setName($name) {
    $this->name = ($this->config->get('prefix')) ? $this->config->get('prefix') . '_' . $name : $name;
    $this->name = preg_replace("/[^\w]/", "_", $this->name);
  }

  private function connect() {
    $this->service = $this->connection->queuesService('cloudQueues', $this->config->get('region'));

    if ($this->config->get('client_id')) {
      $this->service->setClientId($this->config->get('client_id'));
    }

    if (!$this->service->hasQueue($this->name)) {
      $this->createQueue();
    }
    $this->queue = $this->service->getQueue($this->name);
  }
}
