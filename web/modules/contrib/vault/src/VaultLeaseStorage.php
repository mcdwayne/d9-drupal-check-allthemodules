<?php

namespace Drupal\vault;

/**
 * Class VaultLeaseStorage handles storage of leases in Drupal state api.
 *
 * @package Drupal\vault
 */
class VaultLeaseStorage {

  /**
   * The storage handler.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $storage;

  /**
   * VaultLeaseStorage constructor.
   */
  public function __construct() {
    $this->storage = \Drupal::keyValueExpirable("vault_lease");
  }

  /**
   * Returns the data for a lease using Drupal's internal storage key.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   *
   * @return mixed
   *   The data stored in the lease.
   */
  public function getLease($storage_key) {
    $item = $this->getLeaseRaw($storage_key);
    return $item['data'];
  }

  /**
   * Returns the raw state entry for a lease from Drupal's internal storage.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   *
   * @return mixed
   *   The response from state api.
   */
  protected function getLeaseRaw($storage_key) {
    return $this->storage->get($storage_key);
  }

  /**
   * Return all lease items.
   *
   * @return array
   *   Array of lease items.
   */
  public function getAllLeases() {
    $items = $this->storage->getAll();
    $returned = [];
    foreach ($items as $key => $item) {
      $returned[$key] = $item['data'];
    }
    return $returned;
  }

  /**
   * Returns the lease ID for a given storage key.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   *
   * @return string
   *   The Vault lease ID.
   */
  public function getLeaseId($storage_key) {
    $item = $this->getLeaseRaw($storage_key);
    if (empty($item)) {
      return FALSE;
    }
    return $item['lease_id'];
  }

  /**
   * Deletes a lease from storage.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   */
  public function deleteLease($storage_key) {
    $this->storage->delete($storage_key);
  }

  /**
   * Stores a new lease.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   * @param string $lease_id
   *   The lease ID.
   * @param mixed $data
   *   The lease data.
   * @param int $expires
   *   The lease expiry (relative to current time in seconds).
   */
  public function setLease($storage_key, $lease_id, $data, $expires) {
    $payload = [
      'lease_id' => $lease_id,
      'data' => $data,
    ];
    $this->storage->setWithExpire($storage_key, $payload, $expires);
  }

  /**
   * Updates the expiry of an existing lease.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   * @param int $new_expires
   *   The new lease expiry (relative to current time in seconds).
   */
  public function updateLeaseExpires($storage_key, $new_expires) {
    $data = $this->storage->get($storage_key);
    $this->setLease($storage_key, $data['lease_id'], $data['data'], $new_expires);
  }

}
