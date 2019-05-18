<?php

namespace Drupal\prepared_data\Storage;

use Drupal\prepared_data\PreparedDataInterface;

/**
 * Base class with in-memory caching for prepared data storage implementations.
 */
abstract class CachingStorageBase implements StorageInterface {

  /**
   * An array holding in-memory cached data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Keeps in mind how many times data has been loaded.
   *
   * This is used to reset the data in-memory cache
   * to prevent memory exceedance, especially on batch processing.
   *
   * @var int
   */
  protected $loadCount = 0;

  /**
   * Performs actual loading from the storage backend.
   *
   * @param string $key
   *   The data key to load the data for.
   *
   * @return array|null
   *   The prepared data if found, NULL otherwise.
   */
  abstract protected function doLoad($key);

  /**
   * Performs actual saving of prepared data.
   *
   * @param string $key
   *   The data key to save the data for.
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data to save.
   */
  abstract protected function doSave($key, PreparedDataInterface $data);

  /**
   * Performs actual deletion of data which belongs to the given key.
   *
   * @param string $key
   *   The data key which identifies the data to delete.
   */
  abstract protected function doDelete($key);

  /**
   * Performs fetching of the next data record for refreshing.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The fetched record of prepared data to refresh, if given.
   */
  abstract protected function doFetchNext();

  /**
   * {@inheritdoc}
   */
  public function load($key) {
    if (!isset($this->data[$key])) {
      $this->loadCount();
      $this->data[$key] = FALSE;
      if ($data = $this->doLoad($key)) {
        // Cache the instance.
        $this->data[$key] = $data;
      }
    }
    return $this->data[$key] !== FALSE ? $this->data[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    unset($this->data[$key]);
    $this->doDelete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function save($key, PreparedDataInterface $data) {
    if (!isset($this->data[$key])) {
      $this->loadCount();
    }
    $this->data[$key] = $data;
    $this->doSave($key, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    $this->loadCount = 0;
    $this->data = [];
  }

  /**
   * {@inheritdoc}
   */
  public function fetchNext() {
    // Mass-loading of records might exceed memory limits,
    // thus previously loaded records should be removed from cache.
    $this->clearCache();
    return $this->doFetchNext();
  }

  /**
   * Counts loading and clears cache when count limit has been exceeded.
   */
  protected function loadCount() {
    $this->loadCount++;
    if ($this->loadCount > 100) {
      // Clear in-memory caching to prevent memory exceedance.
      $this->clearCache();
    }
  }

}
