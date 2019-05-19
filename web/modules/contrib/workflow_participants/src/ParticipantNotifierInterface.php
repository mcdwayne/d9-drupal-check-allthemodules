<?php

namespace Drupal\workflow_participants;

use Drupal\Core\Entity\EntityInterface;
use Drupal\workflow_participants\Entity\WorkflowParticipantsInterface;

/**
 * Defines an interface for participant notifications.
 */
interface ParticipantNotifierInterface {

  /**
   * Given an updated list of participants, finds newly added participants.
   *
   * @param \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface $participants
   *   The new or updated workflow participants entity.
   *
   * @return \Drupal\user\UserInterface[]
   *   An array of newly added participants.
   */
  public function getNewParticipants(WorkflowParticipantsInterface $participants);

  /**
   * Processes notifications for participants.
   */
  public function processNotifications(WorkflowParticipantsInterface $participants);

  /**
   * Sends a notification to relevant recipients.
   *
   * @param \Drupal\user\UserInterface[] $accounts
   *   List of accounts to notify. This should have already been filtered down
   *   to only new recipients.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the users have been added to as participants.
   */
  public function sendNotifications(array $accounts, EntityInterface $entity);

}
