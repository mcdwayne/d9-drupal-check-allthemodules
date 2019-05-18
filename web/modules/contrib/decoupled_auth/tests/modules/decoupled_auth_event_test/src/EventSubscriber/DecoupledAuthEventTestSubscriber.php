<?php

namespace Drupal\decoupled_auth_event_test\EventSubscriber;

use Drupal\decoupled_auth\AcquisitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to AcquisitionEvent events and add information to the context.
 */
class DecoupledAuthEventTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquisitionEvent::PRE][] = ['setTestContextPre'];
    $events[AcquisitionEvent::POST][] = ['setTestContextPost'];
    return $events;
  }

  /**
   * This method is called when the AcquisitionEvent::PRE event is dispatched.
   *
   * @param \Drupal\decoupled_auth\AcquisitionEvent $event
   *   The acquisition event.
   */
  public function setTestContextPre(AcquisitionEvent $event) {
    $context = &$event->getContext();
    $context['testEventPre'] = TRUE;
  }

  /**
   * This method is called when the AcquisitionEvent::POST event is dispatched.
   *
   * @param \Drupal\decoupled_auth\AcquisitionEvent $event
   *   The acquisition event.
   */
  public function setTestContextPost(AcquisitionEvent $event) {
    $context = &$event->getContext();
    $context['testEventPost'] = TRUE;
  }

}
