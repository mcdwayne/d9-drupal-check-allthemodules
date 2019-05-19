<?php

/**
 * @file
 * Contains \Drupal\timelogin\EventSubscriber\InitSubscriber.
 */

namespace Drupal\timelogin\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent() {
    $logged_in = \Drupal::currentUser()->isAuthenticated();
    //Check user is logedIn
    if ($logged_in) {
      timelogin_user_access();
    }
  }

}
