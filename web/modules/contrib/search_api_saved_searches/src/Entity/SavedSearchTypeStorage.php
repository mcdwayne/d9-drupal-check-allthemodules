<?php

namespace Drupal\search_api_saved_searches\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Provides a storage handler for saved search types.
 *
 * @see \Drupal\search_api_saved_searches\Entity\SavedSearchType
 */
class SavedSearchTypeStorage extends ConfigEntityStorage {

  /**
   * Retrieves the IDs of all types that use a specific notification plugin.
   *
   * @param string $plugin_id
   *   The notification plugin's ID.
   *
   * @return string[]
   *   The IDs of all types that use the given notification plugin.
   */
  public function getTypesForNotificationPlugin($plugin_id) {
    return $this->getQuery()
      ->exists("notification_settings.$plugin_id")
      ->accessCheck(FALSE)
      ->execute();
  }

}
