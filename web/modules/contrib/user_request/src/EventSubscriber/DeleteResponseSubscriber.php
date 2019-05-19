<?php

namespace Drupal\user_request\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the response from a request when configured transtition happens.
 */
class DeleteResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'user_request.pre_transition' => 'removeResponse',
      'user_request.post_transition' => 'deleteResponseEntity',
    ];
  }

  /**
   * Removes the response from the request.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function removeResponse(WorkflowTransitionEvent $event) {
    // Removes the response if the performed transition is that configured to 
    // delete it.
    $transition = $event->getTransition();
    $request = $event->getEntity();
    $request_type = $request->getRequestType();
    if ($transition->getId() == $request_type->getDeletedResponseTransition()) {
      $request->removeResponse();
    }
  }

  /**
   * Deletes the removed response entity.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function deleteResponseEntity(WorkflowTransitionEvent $event) {
    $request = $event->getEntity();
    if ($response = $request->getRemovedResponse()) {
      $response->delete();
    }
  }

}
