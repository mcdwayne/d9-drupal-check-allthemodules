<?php

namespace Drupal\scheduled_executable\Entity\Handler;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage class for scheduled_executable entities.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class, adding
 * required special handling for scheduled_executable entities.
 */
class ScheduledExecutableStorage extends SqlContentEntityStorage {

  /**
   * Find whether items already exist for a given time, group, and key.
   *
   * @param int $execution_time
   *   The execution timestamp to search for.
   * @param string $group
   *   The group name to search for.
   * @param string $key
   *   The key to search for.
   *
   * @return array
   *   An array of IDs. This will be empty if nothing is found.
   */
  public function findDuplicateScheduledItems($execution_time, $group, $key) {
    $ids = $this->database->query('SELECT id FROM {scheduled_executable}
      WHERE
        execution = :execution AND
        group_name = :group AND
        key_name = :key', [
      ':execution' => $execution_time,
      ':group' => $group,
      ':key' => $key,
    ])->fetchCol();

    return $ids;
  }

}
