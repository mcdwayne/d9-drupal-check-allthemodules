<?php

namespace Drupal\workflow_participants;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the workflow participants entity.
 *
 * @see \Drupal\workflow_participants\Entity\WorkflowParticipants
 */
class WorkflowParticipantsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $admin_access = parent::checkAccess($entity, $operation, $account);

    return $admin_access;
  }

}
