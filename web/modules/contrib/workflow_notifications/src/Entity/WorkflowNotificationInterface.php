<?php

namespace Drupal\workflow_notifications\Entity;

use Drupal\workflow\Entity\Workflow;

/**
 *  Defines a Workflow Notification entity
 *
 * @package Drupal\workflow_notifications\Entity
 */
interface WorkflowNotificationInterface {

  /**
   * Returns the Workflow ID of this object.
   *
   * @return string
   *   Workflow Id.
   */
  public function getWorkflowId();

  /**
   * Returns the Workflow object of this object.
   *
   * @return Workflow
   *   Workflow object.
   */
  public function getWorkflow();

  /**
   * @param Workflow $workflow
   */
  public function setWorkflow(Workflow $workflow);

  /**
   * Load WorkflowNotification Id's.
   *
   * @param string $from_sid
   * @param string $to_sid
   * @param string $wid
   * @param string $trigger
   * @param integer $days
   *
   * @return WorkflowNotification[]
   *   An array of Notifications.
   */
  public static function loadMultipleByProperties($from_sid, $to_sid, $wid, $trigger, $days);

}
