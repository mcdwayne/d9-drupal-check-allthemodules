<?php

namespace Drupal\asf;


/**
 * Class AsfAction.
 */
class AsfAction {

  private $action;

  /**
   * Constructor
   *
   * @param $eid
   * @param $time
   * @param $action
   * @param int $fid
   */
  function __construct(){
  }

  /**
   * Create an action in the DB.
   */
  function create($eid, $time, $action, $fid = 0) {
    $this->action = new \stdClass();
    $this->action->eid = $eid;
    $this->action->fid = $fid;
    $this->action->action = $action;
    $this->action->time = $time;
    $this->action->status = ASF_STATUS_PENDING;
  }

  /**
   * Update an action.
   *
   * @param object $action
   *   an action.
   */
  public function update() {
    $num_updated = db_update('asf_schedule')
      ->fields(array(
        'eid' => $this->action->eid,
        'fid' => $this->action->fid,
        'action' => $this->action->action,
        'time' => $this->action->time,
        'status' => $this->action->status,
        'changed' => REQUEST_TIME,
      ))
      ->condition('aid', $this->action->aid, '=')
      ->execute();
    return $num_updated;
  }

  /**
   * Insert an action into the DB.
   *
   * @param object $action
   *   the action object.
   */
  public function insert() {
    $aid = db_insert('asf_schedule')
      ->fields(array(
        'eid' => $this->action->eid,
        'fid' => $this->action->fid,
        'action' => $this->action->action,
        'time' => $this->action->time,
        'status' => $this->action->status,
        'changed' => REQUEST_TIME,
        'created' => REQUEST_TIME,
      ))
      ->execute();
    return $aid;
  }

  /**
   * Delete an action.
   *
   * @param string $action_id
   *   the id of the action in the schedule table
   */
  public function delete($action_id) {
    $num_deleted = db_delete('asf_schedule')
      ->condition('aid', $action_id)
      ->execute();
  }

  /**
   * Dismiss.
   *
   */
  public function dismiss($action) {
    $this->create($action->eid,$action->time,$action->action, $action->fid);
    $this->action->aid = $action->aid;
    $this->action->status = ASF_STATUS_EXECUTED;
    $this->update();
  }
}