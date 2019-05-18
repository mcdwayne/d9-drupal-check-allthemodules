<?php

namespace Drupal\rollback;

use Drupal\Core\Database\Connection;
use Drupal\Component\DateTime\Time;

/**
 * Class RegisteredUpdates.
 */
class RegisteredUpdates {

  /**
   * The array of update objects extending 'RollableUpdate'.
   *
   * @var array
   */
  protected $updates;

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
   * Constructs a new RegisteredUpdates object.
   *
   * @param array $updates
   *   The array of update objects extending 'RollableUpdate'.
   * @param Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param Drupal\Component\DateTime\Time $time
   *   For interacting with the current time.
   */
  public function __construct(array $updates, Connection $database, Time $time) {
    $this->updates = $updates;
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Executes the database updates.
   *
   * Executes one by one, in the order
   * as defined in the implementation of hook_update_N.
   */
  public function run() {
    foreach ($this->updates as $update) {
      // Retrieve the traits of the update class.
      // Available traits are:
      // - Drupal\rollback\Traits\RollbackIfFailed
      // - Drupal\rollback\Traits\ValidationTrait
      // - Drupal\rollback\Traits\ValidateRollback.
      $traits = class_uses($update);

      try {
        // Run the update. Any exceptions will be caught
        // and the update will be considered failed.
        $update->up();

        if (method_exists($update, 'validate')) {
          $result = $update->validate();

          // Update the state based on what was returned from the
          // validate function.
          $this->updateState($result, $update);

          if (!$result) {
            // If the update class has the 'RollbackIfFailed' trait
            // call the 'down' function.
            if (isset($traits['Drupal\\rollback\\Traits\\RollbackIfFailed'])) {
              $update->down();
            }
          }
        }
        else {
          // The update does not have a validate function so
          // set the state to true.
          $this->updateState(TRUE, $update);
        }

      }
      catch (\Exception $ex) {
        // Set the state to failed.
        $this->updateState(FALSE, $update);

        // If the update class has the 'RollbackIfFailed' trait
        // call the 'down' function.
        if (isset($traits['Drupal\\rollback\\Traits\\RollbackIfFailed'])) {
          $update->down();
        }
      }
    }
  }

  /**
   * Update the state value of the row stored in the rollback table.
   *
   * @param bool $state
   *   The state of the update.
   * @param Drupal\rollback\RollableUpdate $update
   *   The array of update objects.
   */
  private function updateState(bool $state, RollableUpdate $update) {
    $this->database->update('rollback')
      ->fields([
        'state' => $state ? 'success' : 'fail',
        'last_run' => $this->time->getCurrentTime(),
      ])
      ->condition('target', serialize(get_class($update)))
      ->condition('schema_version', $update->getSchema())
      ->execute();
  }

}
