<?php

namespace Drupal\eqsf;

use Drupal\eqsf\EqsfAction;


/**
 * Class EqsfSchema.
 */
class EqsfSchema {
  /**
   * Return a list of actions according to your selection.
   *
   * @param array $options
   */
  public static function selectActions($options) {
    $query = \Drupal::database()->select('eq_schedule', 'eq');
    $query->fields('eq');
    if (isset($options['id'])) {
      $query->condition('eq.id', $options['id'], '=');
    }
    if (isset($options['eid'])) {
      $query->condition('eq.eid', $options['eid'], '=');
    }
    if (isset($options['eqid'])) {
      $query->condition('eq.eqid', $options['eqid'], '=');
    }
    if (isset($options['start'])) {
      $query->condition('eq.start', $options['start']['value'], $options['start']['operator']);
    }
    if (isset($options['stop'])) {
      $query->condition('eq.stop', $options['stop']['value'], $options['stop']['operator']);
    }
    if (isset($options['position'])) {
      $query->condition('eq.position', $options['position'], '=');
    }
    //$query->orderBy('start', 'ASC');
    $actions = $query->execute()->fetchAll();

    return $actions;
  }

  /**
   * Delete pending actions of this entity so the newer version can override it.
   */
  public static function deleteSchedule($eid) {
    // Select and delete future actions.
    $options = array(
      'eid' => $eid,
    );
    $existing_actions = EqsfSchema::selectActions($options);
    foreach ($existing_actions as $action) {
      $a = new  EqsfAction();
      $a->delete($action->id);
    }
  }

  /**
   * Prepare scheme for insert in DB.
   */
  public function generateScheme($entity, $eqid, $values) {
    $eid = $entity->id();
    $start_time = $values['startdate'];
    $end_time = $values['enddate'];
    $position = $values['position'];
    $a = new EqsfAction();

    $exists = \Drupal::database()->select('eq_schedule', 'eq')
      ->fields('eq', ['eid'])
      ->condition('eq.eid', $eid)
      ->execute()->fetch();

    $a->create($eid, $eqid, $start_time, $end_time, $position);
    $exists ? $a->update() : $a->insert();
  }
}
