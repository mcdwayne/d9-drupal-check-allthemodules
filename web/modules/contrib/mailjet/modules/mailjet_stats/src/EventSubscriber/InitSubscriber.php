<?php

/**
 * @file
 * Contains \Drupal\mailjet_stats\EventSubscriber\InitSubscriber.
 */

namespace Drupal\mailjet_stats\EventSubscriber;

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
    if (isset($_GET['token'])) {
      $mailjet_campaign_id = $_GET['token'];
      $_SESSION['mailjet_campaign_id'] = $mailjet_campaign_id;
    }
  }
}