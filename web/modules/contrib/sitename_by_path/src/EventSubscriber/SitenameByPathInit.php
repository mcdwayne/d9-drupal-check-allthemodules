<?php

namespace Drupal\sitename_by_path\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\sitename_by_path\SitenameByPathStorage;

/**
 * On page load alter site vars if conditions are right.
 */
class SitenameByPathInit implements EventSubscriberInterface {

  /**
   * On page load alter site vars if conditions are right.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    $entries = SitenameByPathStorage::load();
    $path = \Drupal::service('path.current')->getPath();

    foreach ($entries as $entry) {
      $path_matches = \Drupal::service('path.matcher')->matchPath($path, $entry->path);
      if ($path_matches === TRUE) {

        // Set temporary drupal sitename and frontpage from current site info.
        \Drupal::configFactory()->getEditable('sitename_by_path.vars')
          ->set('sitename', \Drupal::config('system.site')->get('name'))
          ->save();
        \Drupal::configFactory()->getEditable('sitename_by_path.vars')
          ->set('frontpage', \Drupal::config('system.site')->get('page.front'))
          ->save();

        // Overwrite drupal sitename and frontpage.
        $config = \Drupal::service('config.factory')->getEditable('system.site');
        $config->set('name', $entry->sitename)->save();
        $config = \Drupal::service('config.factory')->getEditable('system.site');
        $config->set('page.front', $entry->frontpage)->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

}
