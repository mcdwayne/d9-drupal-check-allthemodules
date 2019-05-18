<?php

namespace Drupal\contacts_events\Guard;

use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\Core\Entity\EntityInterface;

/**
 * Workflow guard for the booking transitions.
 */
class WorkflowGuard implements GuardInterface {

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    if ($workflow->getId() == 'contacts_events_booking_process') {
      // Only allow manual confirmation.
      if ($transition->getId() != 'place') {
        return FALSE;
      }
    }

    return NULL;
  }

}
