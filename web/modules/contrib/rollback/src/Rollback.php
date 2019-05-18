<?php

namespace Drupal\rollback;

use Drupal\Core\Database\Connection;
use Drupal\Component\DateTime\Time;
use Drupal\rollback\Exception\UnknownType;
use Drupal\rollback\Exception\RollbackFailedException;

/**
 * Class Rollback.
 *
 * Handles performing an actual rollback.
 */
class Rollback {

  /**
   * Implements the database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Implements the date component.
   *
   * @var Drupal\Component\DateTime\Time
   */
  protected $time;

  /**
   * Store the updates that have been rolled back.
   *
   * @var array
   */
  private $rolledback;

  /**
   * Construct a new Rollback object.
   *
   * @param Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param Drupal\Component\DateTime\Time $time
   *   For interacting with the current time.
   */
  public function __construct(Connection $database, Time $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Rollback an update.
   *
   * @param string $module
   *   The machine name of the module.
   * @param int $schema
   *   The schema to rollback.
   *
   * @throws Drupal\rollback\Exception\UnknownType
   *   If the target is neither class nor service.
   * @throws Drupal\rollback\Exception\RollbackFailedException
   *   If the update validation returns TRUE after rollback.
   *
   * @return bool|array
   *   Rollback value.
   */
  public function run(string $module, int $schema) {
    // Retrieve the last successful database update from the
    // 'rollback' table.
    $query = $this->database->select('rollback', 'r')
      ->fields('r', [])
      ->condition('r.module', $module)
      ->condition('r.schema_version', $schema, '>=')
      ->condition('r.state', 'registered', '!=')
      ->orderBy('r.schema_version', 'DESC');

    // Execute the query.
    $data = $query->execute();

    // Retrieve the results.
    $result = $data->fetchAll(\PDO::FETCH_OBJ);

    // Return FALSE to let the caller know that there is
    // no available updates to rollback.
    if (empty($result)) {
      return FALSE;
    }

    // Create an object of each update and run the revert.
    foreach ($result as $row) {
      $target = unserialize($row->target);

      // Retrieve the RollableUpdate object.
      if (class_exists($target)) {
        /** @var Drupal\rollback\RollableUpdate $object */
        $object = new $target;
      }
      elseif (\Drupal::hasService($target)) {
        /** @var Drupal\rollback\RollableUpdate $object */
        $object = \Drupal::service($target);
      }
      else {
        // Unable to determine if the update is a class
        // or a service. Perhaps the cache must first be
        // rebuilt?
        throw new UnknownType($update);
      }

      // Retrieve the traits of the update class.
      // Available traits are:
      // - Drupal\rollback\Traits\RollbackIfFailed
      // - Drupal\rollback\Traits\ValidationTrait
      // - Drupal\rollback\Traits\ValidateRollback.
      $traits = class_uses($object);

      // Perform the rollback.
      $object->down();

      if (isset($traits['Drupal\\rollback\\Traits\\ValidateRollback'])) {
        $result = $object->validate();

        if ($result) {
          $this->setState('fail', $row->target, $module, $object);

          // Validation failed, exit out of the update
          // before the schema is updated.
          throw new RollbackFailedException($object);
        }
      }
      else {
        $this->setState('registered', $row->target, $module, $object);
      }

      // In the future this area could be a bit
      // more intelligent, by determining the actual previous
      // schema version and setting to that instead. For now,
      // it takes the rollback schema version and deducts one (1) from it.
      drupal_set_installed_schema_version($row->module, $row->schema_version - 1);

      $this->rolledback[] = $row;
    }

    return $this->rolledback;
  }

  /**
   * Update the state of the rollback in the database.
   *
   * @param string $state
   *   The state to update the row with.
   * @param string $target
   *   Serialized string of the target class or service.
   * @param string $module
   *   The machine name of the module.
   * @param Drupal\rollback\RollableUpdate $object
   *   The update object, used to retrieve the schema.
   */
  private function setState(string $state, string $target, string $module, RollableUpdate $object) {
    // Update the state to 'registered' in the database.
    $this->database->update('rollback')
      ->fields([
        'state' => $state,
        'last_run' => $this->time->getCurrentTime(),
      ])
      ->condition('target', $target)
      ->condition('schema_version', $object->getSchema())
      ->condition('module', $module)
      ->execute();
  }

}
