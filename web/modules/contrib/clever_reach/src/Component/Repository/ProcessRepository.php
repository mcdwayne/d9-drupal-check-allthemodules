<?php

namespace Drupal\clever_reach\Component\Repository;

use CleverReach\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\TaskExecution\Exceptions\ProcessStorageGetException;

/**
 * Process repository class.
 */
class ProcessRepository extends BaseRepository {
  const TABLE_NAME = 'cleverreach_process';

  /**
   * Saves single row of a process.
   *
   * @param string $guid
   *   Unique generated code.
   * @param \CleverReach\Infrastructure\Interfaces\Exposed\Runnable $runner
   *   Runnable object.
   *
   * @throws \Exception
   */
  public function save($guid, Runnable $runner) {
    $this->insert([static::TABLE_PK => $guid, 'runner' => serialize($runner)]);
  }

  /**
   * Gets runner by provided GUID.
   *
   * @param string $guid
   *   Unique generated code.
   *
   * @return \CleverReach\Infrastructure\Interfaces\Exposed\Runnable
   *   Runnable object.
   *
   * @throws ProcessStorageGetException
   *   When process doesn't exist.
   */
  public function getRunner($guid) {
    $process = $this->findById($guid);
    if ($process === NULL) {
      throw new ProcessStorageGetException("Process runner with guid $guid does not exist.");
    }
    return unserialize($process['runner']);
  }

  /**
   * Deletes process for provided GUID.
   *
   * @param string $guid
   *   Unique generated code stored in database.
   */
  public function deleteProcess($guid) {
    $process = $this->findById($guid);
    if ($process === NULL) {
      Logger::logError("Could not delete process with guid $guid");
    }
    else {
      $this->deleteById($guid);
    }
  }

}
