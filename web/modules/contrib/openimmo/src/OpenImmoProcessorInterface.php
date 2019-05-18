<?php

namespace Drupal\openimmo;

/**
 * Processor of openimmo data.
 */
interface OpenImmoProcessorInterface {

  /**
   * Claims an item in the openimmo fetch queue for processing.
   *
   * @return bool|\stdClass
   *   On success we return an item object. If the queue is unable to claim an
   *   item it returns false.
   *
   * @see \Drupal\Core\Queue\QueueInterface::claimItem()
   */
  public function claimQueueItem();

  /**
   * Attempts to drain the queue of tasks for release history data to fetch.
   */
  public function fetchData();

  /**
   * Adds a task to the queue.
   *
   * @param array $source
   *   Associative array of information about the query to fetch data for.
   */
  public function createFetchTask(array $source);

  /**
   * Processes a task to fetch available openimmo data for a single query.
   *
   * @param array $source
   *   Associative array of information about the query to fetch data for.
   *
   * @return bool
   *   TRUE if we fetched passable XML, otherwise FALSE.
   */
  public function processFetchTask(array $source);

  /**
   * Retrieves the number of items in the openimmo fetch queue.
   *
   * @return int
   *   An integer estimate of the number of items in the queue.
   *
   * @see \Drupal\Core\Queue\QueueInterface::numberOfItems()
   */
  public function numberOfQueueItems();

  /**
   * Deletes a finished item from the openimmo fetch queue.
   *
   * @param \stdClass $item
   *   The item returned by \Drupal\Core\Queue\QueueInterface::claimItem().
   */
  public function deleteQueueItem(\stdClass $item);

}
