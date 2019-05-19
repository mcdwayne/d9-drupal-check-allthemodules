<?php

namespace Drupal\wb_404_redirection\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WbStates404Subscriber extends HttpExceptionSubscriberBase {

  protected static function getPriority() {
    // set priority higher than 50 if you want to log "page not found"
    return 0;
  }

  protected function getHandledFormats() {
    return ['html'];
  }

  public function on403(GetResponseForExceptionEvent $event) {
    $request = $event->getRequest();

    if ($request->attributes->get('_route') == 'entity.node.canonical') {
      //get content moderation state
      $node = $request->attributes->get('node');
      $moderation_state = $node->get('moderation_state')->getValue();
      $state = $moderation_state[0]['target_id'];

      //get saved states in configurations
      $saved_states = \Drupal::config('wb_404_redirection.settings')->get('state_transition');

      //check the state existing in saved configuration states and set the 404 exception.
      if(in_array($state, $saved_states))
        $event->setException(new NotFoundHttpException());
      }
  }

}