<?php

namespace Drupal\vault;

use Vault\CachedClient;
use Vault\Exceptions\ClientException;

/**
 * Wrapper for \Vault\Client providing some helper methods.
 *
 * It also acts as a translation layer between vault leases and drupal entities
 * implementing leases (like keys).
 */
class VaultClient extends CachedClient {

  public const API = 'v1';

  /**
   * The lease storage object.
   *
   * @var \Drupal\Vault\VaultLeaseStorage
   */
  protected $leaseStorage;

  /**
   * Sets the leaseStorage property.
   *
   * @param \Drupal\Vault\VaultLeaseStorage $leaseStorage
   *   The lease storage object.
   */
  public function setLeaseStorage(VaultLeaseStorage $leaseStorage) {
    $this->leaseStorage = $leaseStorage;
  }

  /**
   * Makes a LIST request against an endpoint.
   *
   * @param string $url
   *   Request URL.
   * @param array $options
   *   Options to pass to request.
   *
   * @return \Vault\ResponseModels\Response
   *   Response from vault server.
   *
   * @todo implement this in upstream class.
   */
  public function list($url, array $options = []) {
    return $this->responseBuilder->build($this->send(new Request('LIST', $url), $options));
  }

  /**
   * Queries list of secret engine mounts on the configured vault instance.
   *
   * @return \Vault\ResponseModels\Response
   *   Response from vault server.
   */
  public function listMounts() {
    return $this->read('/sys/mounts')->getData();
  }

  /**
   * Queries list of particular secret backends.
   *
   * @param array $engine_types
   *   Array of secret engine types to list.
   *
   * @return array
   *   Array of secret engine mounts.
   */
  public function listSecretEngineMounts(array $engine_types) {
    $data = $this->listMounts();
    return array_filter($data, function ($v) use ($engine_types) {
      return in_array($v['type'], $engine_types);
    });
  }

  /**
   * Stores a lease.
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
  public function storeLease($storage_key, $lease_id, $data, $expires) {
    $this->leaseStorage->setLease($storage_key, $lease_id, $data, $expires);
  }

  /**
   * Retrieve a lease.
   */
  public function retrieveLease($storage_key) {
    return $this->leaseStorage->getLease($storage_key);
  }

  /**
   * Revokes a lease.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   */
  public function revokeLease($storage_key) {
    $this->logger->debug(sprintf("attempting to revoke lease for %s", $storage_key));
    $lease_id = $this->leaseStorage->getLeaseId($storage_key);
    if (empty($lease_id)) {
      $this->logger->error(sprintf("could not find lease for %s", $storage_key));
      return TRUE;
    }

    try {
      $data["lease_id"] = $lease_id;
      $response = $this->put($this->buildPath("/sys/leases/revoke"), ['json' => $data]);

      if (is_null($response->getRequestId())) {
        throw new ClientException("null response from server revoking lease for " . $storage_key);
      }
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("Failed revoking lease %s", $lease_id));
      $this->leaseStorage->deleteLease($lease_id);
      return FALSE;
    }

    $this->leaseStorage->deleteLease($lease_id);
    return TRUE;

  }

  /**
   * Renews a lease.
   *
   * @param string $storage_key
   *   The storage key. Something like "key:key_machine_id".
   * @param int $increment
   *   The number of seconds to extend the release by.
   *
   * @return bool
   *   TRUE if successful, FALSE if failed.
   */
  public function renewLease($storage_key, $increment) {
    $this->logger->debug(sprintf("attempting to renew lease for %s", $storage_key));
    $lease_id = $this->leaseStorage->getLeaseId($storage_key);
    try {
      if (empty($lease_id)) {
        throw new ClientException("no valid lease for " . $storage_key);
      }

      $data = [
        "lease_id" => $lease_id,
        "increment" => $increment,
      ];
      $response = $this->put($this->buildPath("/sys/leases/renew"), ['json' => $data]);
      if (is_null($response->getRequestId())) {
        throw new ClientException("null response from server renewing lease for " . $storage_key);
      }

      $new_expires = $response->getLeaseDuration();
      $this->leaseStorage->updateLeaseExpires($storage_key, $new_expires);
    }
    catch (\Exception $e) {
      $this->logger->error(sprintf("Failed renewing lease %s", $lease_id));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Helper method to renew all existing leases.
   *
   * @param int $increment
   *   Additional length to request leases for. Should be _at least_ the number
   *   of seconds between cron runs.
   */
  public function renewAllLeases($increment) {
    $leases = $this->leaseStorage->getAllLeases();
    foreach ($leases as $key => $value) {
      $response = $this->renewLease($key, $increment);
      if (!$response) {
        // Failed to renew the lease - remove it from state.
        $this->logger->info(sprintf("revoking expired lease for %s", $key));
        $this->revokeLease($key);
      }
    }
  }

}
