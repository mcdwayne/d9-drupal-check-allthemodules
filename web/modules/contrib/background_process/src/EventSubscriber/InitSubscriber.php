<?php

namespace Drupal\background_process\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Default controller for the background_process module.
 */
class InitSubscriber implements EventSubscriberInterface {

  /**
   * Implements to Subscribe Events.
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  /**
   * Implements On Event.
   */
  public function onEvent() {
    // Only determine if we're told to do so.
    if (empty($_SESSION['background_process_determine_default_service_host'])) {
      return;
    }
    // Don't determine on check-token page, to avoid infinite loop.
    if (strpos($_GET['q'], 'background-process/check-token') === 0) {
      return;
    }
    if (\Drupal::config('system.site')->get('install_task') != 'done') {
      return;
    }

    // Determine the default service host.
    background_process_determine_and_save_default_service_host();
    unset($_SESSION['background_process_determine_default_service_host']);
  }

}
