<?php

namespace Drupal\contacts;

use Drupal\Core\TempStore\SharedTempStoreFactory;

/**
 * Tracks whether the dashboard is in manage mode.
 *
 * @package Drupal\contacts
 */
class ManageDashboardHelper {

  /**
   * Temp store.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * ManagedDashboard constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   Temp store factory.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('contacts');
  }

  /**
   * Whether a different user is in manage mode.
   *
   * @return bool
   *   Whether a different user is in manage mode.
   */
  public function otherUserInManageMode() : bool {
    return !$this->isInManageMode() && $this->tempStore->get('manage_mode');
  }

  /**
   * Whether the current user is in manage mode.
   *
   * @return bool
   *   Whether the current user is in manage mode.
   */
  public function isInManageMode() : bool {
    return $this->tempStore->getIfOwner('manage_mode') ?? FALSE;
  }

  /**
   * Sets manage mode.
   *
   * @param bool $manage_mode
   *   Whether manage mode is enabled (TRUE) or disabled (FALSE).
   * @param bool $force
   *   Whether to override the current user in manage mode.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function setManageMode(bool $manage_mode, bool $force = FALSE) {
    if ($manage_mode) {
      if ($force) {
        $this->tempStore->set('manage_mode', TRUE);
      }
      else {
        $this->tempStore->setIfOwner('manage_mode', TRUE);
      }
    }
    else {
      if ($force) {
        $this->tempStore->delete('manage_mode');
      }
      else {
        $this->tempStore->deleteIfOwner('manage_mode');
      }
    }
  }

  /**
   * Gets the user currently in manage mode.
   *
   * @return int|null
   *   ID of the user in manage mode, or NULL if no user is in manage mode.
   */
  public function getManageModeUser() : ?int {
    $metadata = $this->tempStore->getMetadata('manage_mode');
    return $metadata ? $metadata->owner : NULL;
  }

}
