<?php

namespace Drupal\rollback;

use Drupal\Core\Database\Connection;
use Drupal\Component\DateTime\Time;
use Drupal\rollback\Exception\RollableUpdateMissingException;
use Drupal\rollback\Exception\SchemaNullException;
use Drupal\rollback\Exception\UnknownType;

/**
 * Class Register.
 */
class Register {

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
   * Updatetes.
   *
   * @var array
   *   Store the array of updates before they're returned
   *   in a RegisteredUpdates object.
   */
  private $updates = [];

  /**
   * Constructs a new Register object.
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
   * Register updates that have functionality to be rolled back.
   *
   * @param string $module
   *   The machine-readable name of the module.
   * @param array $updates
   *   Classes extending the UpdateRollback abstract class.
   *
   * @throws Drupal\rollback\Exception\UnknownType
   *   If the update class or service cannot be found.
   * @throws Drupal\rollback\Exception\RollableUpdateMissingException
   *   If the update class does not extend 'RollableUpdate'.
   * @throws Drupal\rollback\Exception\SchemaNullException
   *   If the schema property has not been given a value.
   */
  public function register(string $module, array $updates) {
    // Check to see if the updates haven't already been
    // registered.
    foreach ($updates as $update) {
      // Retrieve the RollableUpdate object, the RollableUpdate object
      // can be a Drupal service utilising dependency injection as well
      // as a normal class extending RollableUpdate.
      if (class_exists($update)) {
        /** @var Drupal\rollback\RollableUpdate $object */
        $object = new $update;
      }
      elseif (\Drupal::hasService($update)) {
        /** @var Drupal\rollback\RollableUpdate $object */
        $object = \Drupal::service($update);
      }
      else {
        // Unable to determine if the update is a class
        // or a service. Perhaps the cache must first be
        // rebuilt?
        throw new UnknownType($update);
      }

      // Check the object is an instance of the 'RollableUpdate'
      // abstract class.
      if (!$object instanceof RollableUpdate) {
        throw new RollableUpdateMissingException($update);
      }

      // Ensure the schema property on the class has a value.
      // e.g. protected $schema = 8101;.
      if (is_null($object->getSchema())) {
        throw new SchemaNullException($update);
      }

      // Store the new object in the updates array for later use.
      $this->updates[] = $object;

      /** @var Drupal\Core\Database\Query\SelectInterface $query */
      $query = $this->database->select('rollback', 'r')
        ->fields('r', [])
        ->condition('r.schema_version', $object->getSchema())
        ->condition('r.target', serialize(get_class($object)))
        ->condition('r.module', $module);

      // Execute the query and retrieve the results.
      $data = $query->execute();
      $results = $data->fetchAll(\PDO::FETCH_OBJ);

      if (empty($results)) {
        // Insert the Rollable update in to the rollback table.
        $this->database->insert('rollback')
          ->fields([
            'module' => $module,
            'schema_version' => $object->getSchema(),
            'target' => serialize(get_class($object)),
            'registered_at' => $this->time->getCurrentTime(),
          ])
          ->execute();
      }
    }

    // By this point all updates should be registered, and loaded
    // in to the class $updates variable. From here, we can then
    // return the updates in a new 'RegisteredUpdates' object which
    // contains a function ('run') to allow the updates to be executed.
    return new RegisteredUpdates($this->updates, $this->database, $this->time);
  }

}
