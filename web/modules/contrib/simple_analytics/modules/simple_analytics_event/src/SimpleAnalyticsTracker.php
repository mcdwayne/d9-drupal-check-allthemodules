<?php

namespace Drupal\simple_analytics_event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\simple_analytics\SimpleAnalyticsEvents;

/**
 * Simple Analytics Events Listner functions.
 */
class SimpleAnalyticsTracker implements EventSubscriberInterface {

  /**
   * Action on tracking event.
   *
   * @param Drupal\simple_analytics\SimpleAnalyticsEvents $event
   *   The Event to process.
   */
  public function saTrack(SimpleAnalyticsEvents $event) {
    $data = $event->getData();
    $text = $this->t("Tracking event from SimpleAnalytics. Signature : :sig", [':sig' => $data['SIGNATURE']]);
    drupal_set_message($text);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority greater than 200 = No Cache (like hook_boot).
    $events = [];
    $events[SimpleAnalyticsEvents::TRACK][] = ['saTrack', 800];
    return $events;
  }

}
