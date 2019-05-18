<?php
/**
 * @file
 * Definition of Drupal\dfw\DfwSubscriber.
 */

namespace Drupal\dfw;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;


/**
 * Locale Config helper
 *
 * $config is always a DrupalConfig object.
 */
class DfwSubscriber implements EventSubscriberInterface {

  /**
   * @var array
   * Events store
   */
  static protected $stored_events = array();

  /**
   * Override configuration values with localized data.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function DfwLoad(GetResponseEvent $event) {
    // @todo remove this debug code
    //drupal_set_message('Dfw: subscribed');
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('DfwLoad', 99999);
    return $events;
  }
}
