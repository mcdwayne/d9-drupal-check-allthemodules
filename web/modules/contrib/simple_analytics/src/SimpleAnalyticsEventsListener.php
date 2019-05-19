<?php

namespace Drupal\simple_analytics;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Simple Analytics Events Listner functions.
 */
class SimpleAnalyticsEventsListener implements EventSubscriberInterface {

  /**
   * Track event on request call.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @see Symfony\Component\HttpKernel\KernelEvents
   */
  public function simpleAnalyticsTrack(GetResponseEvent $event) {

    $config = SimpleAnalyticsHelper::getConfig();
    // Check S.A tracker and server side tracking.
    if (!$config->get('sa_tracker') || !$config->get('sa_tracker_server')) {
      return;
    }

    // Check no track conditions.
    if (SimpleAnalyticsHelper::checkNotrackConditions($config)) {
      return;
    }

    // Ready to track, Build data array..
    $data = [];
    $data['CAMP'] = "";
    $data['REQUEST_URI'] = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "" . $_SERVER['REQUEST_URI'];
    $data['REFERER'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
    $data['MOBILE'] = SimpleAnalyticsService::mobileDetecte();
    $data['SCREEN'] = "";
    $data['LANGUAGE'] = simple_analytics_get_current_language()->getId();
    $data['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : "";
    $data['REMOTE_ADDR'] = simple_analytics_get_client_ip();
    $data['BOT'] = SimpleAnalyticsService::botDetecte();
    $data['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
    $data['CLOSE'] = FALSE;
    $data['SERVEUR'] = json_encode($_SERVER);

    // @note : Here can't get the title because not set yet.
    $data['TITLE'] = "";

    // Add to stat.
    SimpleAnalyticsActions::setStat($data);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority greater than 200 = No Cache (like hook_boot).
    $events = [];
    $events[KernelEvents::REQUEST][] = ['simpleAnalyticsTrack', 300];
    return $events;
  }

}
