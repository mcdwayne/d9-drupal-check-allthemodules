<?php

/**
 * @file
 * Contains \Drupal\testmodule\ServiceSubscriber.
 */

namespace Drupal\testmodule\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ServiceSubscriber.
 *
 * @package Drupal\testmodule
 */
class ServiceSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.response'] = ['HandleResponse'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.response event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function HandleResponse(Event $event) {
    drupal_set_message('Event kernel.response thrown by Subscriber in module testmodule.', 'status', TRUE);
  }

}
