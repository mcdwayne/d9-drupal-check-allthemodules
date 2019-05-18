<?php

namespace Drupal\dblog_persistent;

/**
 * Interface DbLogPersistentLoaderInterface.
 */
interface DbLogPersistentStorageInterface {

  /**
   * List all types currently stored.
   *
   * @return string[]
   */
  public function getTypes(): array;

  /**
   * Count the messages of a channel.
   *
   * @param string $channel
   *
   * @return int
   */
  public function countChannel(string $channel): int;

  /**
   * Delete all messages in a channel.
   *
   * @param string $channel
   *
   * @return int
   *   Returns the number of entries deleted.
   */
  public function clearChannel(string $channel): int;

  /**
   * Write log message.
   *
   * @param string $channel
   * @param array $fields
   */
  public function writeLog(string $channel, array $fields);

  /**
   * Get messages from a specific channel.
   *
   * @param string $channel
   *   The channel to select.
   * @param int|NULL $count
   *   The page size.
   * @param array|NULL $header
   *   An optional table array to use for sorting.
   *
   * @return iterable
   */
  public function getChannel(string $channel, int $count = NULL, array $header = NULL);

  /**
   * Retrieve a single event.
   *
   * @param int $id
   *
   * @return mixed
   */
  public function getEvent(int $id);
}
