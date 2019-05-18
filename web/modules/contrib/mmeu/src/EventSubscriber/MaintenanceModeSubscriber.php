<?php
/**
 * @file
 * Contains \Drupal\mmeu\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\mmeu\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * Put the site into online mode
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $request = $event->getRequest();
    $current_path = \Drupal::service('path.current')->getPath();
    if (substr($current_path, 0, 1) == '/') {
      $path = substr($current_path, 1, strlen($current_path)-1);
    } else {
      $path = $current_path;
    }

    $mmeu_urls = \Drupal::config('mmeu.settings')->get('mmeu');
    $alias_path = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
    $config = \Drupal::configFactory()->getEditable('mmeu.system.maintenance_mode');
    if ($path != 'admin/config/development/maintenance') {
      if ($config->get('system_maintenance_mode') == TRUE) {
        \Drupal::state()->set('system.maintenance_mode', TRUE);
      }
    }
    if (\Drupal::state()->get('system.maintenance_mode') == TRUE && $mmeu_urls != '') {
      if (\Drupal::service('path.matcher')->matchPath($path, $mmeu_urls) || \Drupal::service('path.matcher')->matchPath($alias_path, $mmeu_urls) || (preg_match('/<front>/', $mmeu_urls) && $path == '')) {
        \Drupal::state()->set('system.maintenance_mode', FALSE);
        $config->set('system_maintenance_mode', TRUE)->save();
      }
    }
    else {
      $config->set('system_maintenance_mode', FALSE)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestMaintenance', 40);
    return $events;
  }

}
