<?php

namespace Drupal\locker\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Locker Event Subscriber.
 */
class LockerSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function checkForRedirection(GetResponseEvent $event) {

    global $base_url;
    $current_page = \Drupal::service('path.current')->getPath();
    $config = \Drupal::config('locker.settings');
    $active_value = $config->get('locker_site_locked', NULL);
    $uri = $config->get('locker_custom_url');

    $unlock_time = $config->get('unlock_datetime');

    if (!empty($unlock_time) && strtotime($unlock_time) <= strtotime('now')) {
      $active_value = 'expired';
    }

    if ($active_value == 'yes') {
      if ($current_page != '/' . $uri) {
        if (!isset($_SESSION['locker_unlocked'])) {
          $event->setResponse(new RedirectResponse($base_url . '/' . $uri));
        }
      }
      elseif ($current_page == '/' . $uri) {
        if (isset($_SESSION['locker_unlocked']) && $_SESSION['locker_unlocked'] == 'yes') {
          $event->setResponse(new RedirectResponse($base_url));
          \Drupal::service('session')->getFlashBag()->clear();
        }
      }
    } else if($active_value == null) {
        if($current_page == '/unlock.html') {
            $event->setResponse(new RedirectResponse($base_url));
        }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

}
