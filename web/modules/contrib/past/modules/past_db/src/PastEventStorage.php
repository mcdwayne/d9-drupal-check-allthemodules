<?php

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\past_db\Entity\PastEvent;

/**
 * Defines a Controller class for past events.
 */
class PastEventStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    $this->deleteArgumentData($entities);
    parent::doDelete($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {

    /** @var PastEvent $entity */
    if (!$entity->isNew() && $entity->argumentsChanged()) {
      // First, delete existing arguments and data.
      $this->deleteArgumentData([$entity]);
    }

    // Save the event itself.
    $result = parent::doSave($id, $entity);

    if (!$entity->argumentsChanged()) {
      return $result;
    }

    // Save the arguments.
    foreach ($entity->getArguments() as $argument) {
      /** @var PastEventArgument $argument */
      $argument->ensureType();
      $insert = $this->database->insert('past_event_argument')
        ->fields([
          'event_id' => $entity->id(),
          'name' => $argument->getKey(),
          'type' => $argument->getType(),
          'raw' => $argument->getRaw(),
        ]);
      try {
        $argument_id = $insert->execute();
      }
      catch (\Exception $e) {
        watchdog_exception('past', $e);
      }

      // Save the argument data.
      if ($argument->getOriginalData()) {
        $this->insertData($argument_id, $argument->getOriginalData());
      }
    }

    // Update child events to use the parent_event_id.
    if ($child_events = $entity->getChildEvents()) {
      $this->database->update('past_event')
          ->fields([
            'parent_event_id' => $entity->id(),
          ])
          ->condition('event_id', $child_events)
          ->execute();
    }

    return $result;
  }

  /**
   * Inserts argument data in the database.
   *
   * @param int $argument_id
   *   Id of the argument that the data belongs to.
   * @param mixed $data
   *   The argument data.
   * @param int $parent_data_id
   *   (optional) Id of the parent data, if data is nested.
   */
  protected function insertData($argument_id, $data, $parent_data_id = 0) {
    $insert = $this->database->insert('past_event_data')
      ->fields(['argument_id', 'parent_data_id', 'type', 'name', 'value', 'serialized']);
    if (is_array($data) || is_object($data)) {
      foreach ($data as $name => $value) {

        // @todo: Allow to make this configurable. Ignore NULL.
        if ($value === NULL) {
          continue;
        }

        $insert->values([
          'argument_id' => $argument_id,
          'parent_data_id' => $parent_data_id,
          'type' => is_object($value) ? get_class($value) : gettype($value),
          'name' => $name,
          // @todo: Support recursive inserts.
          'value' => is_scalar($value) ? $value : serialize($value),
          'serialized' => is_scalar($value) ? 0 : 1,
        ]);
      }
    }
    else {
      $insert->values([
        'argument_id' => $argument_id,
        'parent_data_id' => 0,
        'type' => gettype($data),
        'name' => '',
        'value' => $data,
        'serialized' => 0,
      ]);
    }
    try {
      $insert->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('past', $e);
    }
  }

  /**
   * Delete existing arguments and data for the passed in events.
   *
   * @param \Drupal\past_db\Entity\PastEvent[] $entities
   *   The past events to delete data for.
   */
  protected function deleteArgumentData($entities) {
    $argument_ids = $this->database->select('past_event_argument')
      ->fields('past_event_argument', ['argument_id'])
      ->condition('event_id', array_map(function (PastEvent $event) { return $event->id(); }, $entities), 'IN')
      ->execute()
      ->fetchCol();
    /** @var PastEvent $entity */

    if (!empty($argument_ids)) {
      $this->database->delete('past_event_data')
        ->condition('argument_id', $argument_ids, 'IN')
        ->execute();
      $this->database->delete('past_event_argument')
        ->condition('argument_id', $argument_ids, 'IN')
        ->execute();
    }
  }

}
