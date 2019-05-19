<?php

/**
 * @file
 * Contains \Drupal\tarpit\Controller\PageController.
 */
namespace Drupal\tarpit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tarpit\Event\InsideEvent;

class PageController extends ControllerBase {

  public function main() {
    $event = new InsideEvent();
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(InsideEvent::EVENT_NAME, $event);

    return array(
      '#markup' => $event::generateRandomTextAndLinks()
    );
  }

}
