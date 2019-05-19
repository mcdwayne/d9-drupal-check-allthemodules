<?php

/**
 * @file
 * Contains \Drupal\smart_ip\EventSubscriber\GeolocateUserSubscriber.
 */

namespace Drupal\smart_ip\EventSubscriber;

use Drupal\smart_ip\SmartIp;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Allows Smart IP to act on HTTP request event.
 *
 * @package Drupal\smart_ip\EventSubscriber
 */
class GeolocateUserSubscriber implements EventSubscriberInterface {

  /**
   * Initiate user geolocation.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The response event, which contains the current request.
   */
  public function geolocateUser(GetResponseEvent $event) {
    // Check to see if the page is one of those allowed for geolocation.
    if (!SmartIp::checkAllowedPage()) {
      // This page is not on the list to acquire/update user's geolocation.
      return;
    }
    // Save a database hit.
    SmartIp::updateUserLocation();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['geolocateUser'];
    return $events;
  }

}
