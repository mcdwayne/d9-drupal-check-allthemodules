<?php

namespace Drupal\eqsf;


/**
 * Class EqsfAction.
 */
class EqsfAction {

  private $action;

  /**
   * Constructor
   *
   * @param $id
   */
  function __construct() {
  }

  /**
   * Create an action in the DB.
   */
  function create($eid, $eqid, $start, $stop, $position) {
    $this->action = new \stdClass();
    $this->action->eid = $eid;
    $this->action->eqid = $eqid;
    $this->action->start = $start;
    $this->action->stop = $stop;
    $this->action->position = $position;
  }

  /**
   * Insert an action into the DB.
   *
   * @param object $action
   *   the action object.
   */
  public function insert() {
    $id = \Drupal::database()->insert('eq_schedule')
      ->fields(array(
        'eid'   => $this->action->eid,
        'eqid'  => $this->action->eqid,
        'start' => (int) $this->action->start,
        'stop'  => (int) $this->action->stop,
        'position' => (int) $this->action->position,
      ))->execute();
    return $id;
  }

  /**
   * update an action in the DB.
   */
  function update() {
    \Drupal::database()->update('eq_schedule')
      ->condition('eid', $this->action->eid)
      ->fields(array(
        'eid'   => $this->action->eid,
        'eqid'  => $this->action->eqid,
        'start' => (int) $this->action->start,
        'stop'  => (int) $this->action->stop,
        'position' => (int) $this->action->position,
      ))
      ->execute();
  }

  /**
   * Delete an action.
   *
   * @param string $action_id
   *   the id of the action in the schedule table
   */
  public function delete($action_id) {
    $num_deleted = \Drupal::database()->delete('eq_schedule')
      ->condition('eid', $action_id)
      ->execute();
  }
}
