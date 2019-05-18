<?php

namespace Drupal\hostip\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [ KernelEvents::REQUEST => ['onEvent'] ];
  }


  public function onEvent() {
    if (empty($_SESSION['hostip_data'])) {
      $_SESSION['hostip_data'] = hostip_get_iptocountry_info();
    }
  }
}
