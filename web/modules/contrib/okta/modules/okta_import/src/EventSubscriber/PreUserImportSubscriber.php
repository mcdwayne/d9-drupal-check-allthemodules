<?php

namespace Drupal\okta_import\EventSubscriber;

use Drupal\okta_import\Event\PreUserImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class PreUserImportSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PreUserImportEvent::OKTA_IMPORT_PREUSERIMPORT] = 'doPreUserImport';
    return $events;
  }

  /**
   * Alter user before pre submit.
   *
   * @param \Drupal\okta_import\Event\PreUserImportEvent $event
   *   Pre Submit Event.
   */
  public function doPreUserImport(PreUserImportEvent $event) {
    $user = $event->getUser();
    // $user['profile']['firstName'] = 'Janak';
    // $user['profile']['lastName'] = 'Singh';
    // ksm($user);
    $event->setUser($user);
  }

}
