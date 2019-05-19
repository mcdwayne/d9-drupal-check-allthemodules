<?php

namespace Drupal\workbench_notifier\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\workbench_moderation\Event\WorkbenchModerationEvents;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Eventlistener class for state transition.
 *
 * @package Drupal\workbench_notifier\EventSubscriber
 */
class StateTransitionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[WorkbenchModerationEvents::STATE_TRANSITION][] = ['onNodeStateTransitions', 0];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onNodeStateTransitions($event) {
    $from_state = $event->getStateBefore();
    $to_state = $event->getStateAfter();
    $notifierDetails = $this->getWorkbenchNotifierRoles($from_state, $to_state);
    foreach ($notifierDetails['roles'] as $role) {
      $users = $this->getUsers($role);
      foreach ($users as $user) {
        $this->recordNotification($notifierDetails['wnid'], $event->getEntity()->id(), $event->getEntity()->getLoadedRevisionId(), $user, 1);
      }
    }
  }

  /**
   * Records the latest revision of a given entity.
   *
   * @param $wid
   *   The machine name of the type of entity.
   * @param $entity_id
   *   The Entity ID in question.
   * @param $revision_id
   *   The revision ID that is now the latest revision.
   * @param $uid
   *   The user id to be notified.
   * @param $status
   *   The user read or not read notification.
   *
   * @return int
   *   One of the valid returns from a merge query's execute method.
   */
  private function recordNotification($wid, $entity_id, $revision_id, $uid, $status) {
    return \Drupal::database()->merge('workbench_notifier_tracker')
      ->keys([
        'wnid' => $wid,
        'entity_id' => $entity_id,
        'uid' => $uid,
      ])
      ->fields([
        'revision_id' => $revision_id,
        'status' => $status,
      ])
      ->execute();
  }

  /**
   * Load the moderation roles.
   *
   * @param string $from_state
   *   From state of the transition.
   * @param string $to_state
   *   To state of the transition.
   *
   * @return mixed
   *   Returns notification roles
   */
  private function getWorkbenchNotifierRoles($from_state, $to_state) {
    $query = \Drupal::database()->select('workbench_notifiers', 'wn');
    $query->leftJoin('workbench_notifier_roles', 'wnr', 'wnr.wnid = wn.wnid');

    $query->fields('wnr', ['rid', 'wnid']);

    $query->condition('from_name', $from_state);
    $query->condition('to_name', $to_state);

    $details = $query->execute()->fetchAll();

    $moderationRoles = [];
    foreach ($details as $detail) {
      $moderationRoles['wnid'] = $detail->wnid;
      if (!is_null($detail->rid)) {
        $moderationRoles['roles'][$detail->rid] = $detail->rid;
      }
    }
    return $moderationRoles;
  }

  /**
   * Load all users on the roles.
   *
   * @param string $role
   *   Role.
   *
   * @return mixed
   *   Returns uid of the role
   */
  private function getUsers($role) {
    $users = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', $role)
      ->execute();
    return $users;
  }

}
