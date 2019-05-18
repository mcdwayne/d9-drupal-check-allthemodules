<?php

namespace Drupal\prepared_data\Storage;

use Drupal\prepared_data\PreparedDataInterface;

/**
 * The interface for the storage of prepared data.
 *
 * By default, this module stores the data
 * in the database. You could write your own storage
 * implementation and exchange the current storage implementation.
 * If you would switch between different storages, you would
 * have to manually delete the database table "prepared_data".
 */
interface StorageInterface {

  /**
   * Load prepared data for the given key from the storage.
   *
   * @param string $key
   *   The data key to load the data for.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The prepared data as wrapped object or NULL if not found.
   */
  public function load($key);

  /**
   * Save prepared data for the given key.
   *
   * @param $key
   *   The data key to save the data for.
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data so save.
   */
  public function save($key, PreparedDataInterface $data);

  /**
   * Delete data which belongs to the given key.
   *
   * @param string $key
   *   The data key which identifies the data to delete.
   */
  public function delete($key);

  /**
   * Clears all cached data-sets.
   */
  public function clearCache();

  /**
   * Fetches the next prepared data record for refreshing.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The fetched record of prepared data to refresh, if given.
   */
  public function fetchNext();

  /**
   * Flags data records to be refreshed.
   *
   * @param array $keys
   *   A list of keys of data to be refreshed.
   *   When the list is empty, all existing records are being flagged.
   */
  public function flagToRefresh(array $keys = []);

}
