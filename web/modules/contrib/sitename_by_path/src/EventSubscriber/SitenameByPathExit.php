<?php

namespace Drupal\sitename_by_path\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * On page exit revert site vars.
 */
class SitenameByPathExit implements EventSubscriberInterface {

  /**
   * On page exit revert site vars.
   */
  public function terminate() {
    $path = \Drupal::service('path.current')->getPath();
    if (strpos($path, 'admin') !== 1) {
      // Reset drupal sitename and frontpage from temp vars.
      $config = \Drupal::service('config.factory')->getEditable('system.site');
      $config->set('name', \Drupal::config('sitename_by_path.vars')->get('sitename'))->save();
      $config = \Drupal::service('config.factory')->getEditable('system.site');
      $config->set('page.front', \Drupal::config('sitename_by_path.vars')->get('frontpage'))->save();
    }
    else {
      \Drupal::configFactory()->getEditable('sitename_by_path.vars')
        ->set('sitename', \Drupal::config('system.site')->get('name'))
        ->save();
      \Drupal::configFactory()->getEditable('sitename_by_path.vars')
        ->set('frontpage', \Drupal::config('system.site')->get('page.front'))
        ->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['terminate'];
    return $events;
  }

}
