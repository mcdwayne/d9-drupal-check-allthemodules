<?php

namespace Drupal\okta_import\EventSubscriber;

use Drupal\okta_import\Event\PostUserImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class PostUserImportSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PostUserImportEvent::OKTA_IMPORT_POSTUSERIMPORT] = 'doPostUserImport';
    return $events;
  }

  /**
   * Alter user before post submit.
   *
   * @param \Drupal\okta_import\Event\PostUserImportEvent $event
   *   Post Submit Event.
   */
  public function doPostUserImport(PostUserImportEvent $event) {
    // $user = $event->getUser();
    // ksm($user);
    // $event->setUser($user);
  }

}
