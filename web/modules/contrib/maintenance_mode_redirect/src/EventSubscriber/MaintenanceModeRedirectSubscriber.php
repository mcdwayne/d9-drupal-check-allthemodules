<?php

namespace Drupal\maintenance_mode_redirect\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Class MaintenanceModeRedirectSubscriber.
 *
 * @package Drupal\maintenance_mode_redirect\EventSubscriber
 */
class MaintenanceModeRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set priority higher than core MaintenanceModeSubcriber events.
    $events[KernelEvents::REQUEST][] = array('checkForRedirection', 50);
    return $events;
  }

  /**
   * Check available redirection.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event object.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    $allowed_path = array(
      '/user',
      '/user/login',
      '/user/password',
    );
    $current_path = \Drupal::service('path.current')->getPath();
    if (!in_array($current_path, $allowed_path)) {
      $config = \Drupal::config('maintenance_mode_redirect.settings');
      // If system maintenance mode and enabled URL redirect is enabled,
      // redirect to a different domain.
      $maintenance_enabled = \Drupal::state()->get('system.maintenance_mode');
      $maintenance_url_redirect_enabled =
        $config->get('maintenance_mode_redirect_active');

      if (
        $maintenance_enabled === 1 &&
        $maintenance_url_redirect_enabled &&
        !\Drupal::currentUser()
          ->hasPermission('access site in maintenance mode')
      ) {
        $url_to_redirect = $config->get('maintenance_mode_redirect_url');
        $event->setResponse(new TrustedRedirectResponse($url_to_redirect));
      }
    }
  }

}
