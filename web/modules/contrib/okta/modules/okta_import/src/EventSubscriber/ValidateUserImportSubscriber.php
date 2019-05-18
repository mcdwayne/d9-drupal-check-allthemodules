<?php

namespace Drupal\okta_import\EventSubscriber;

use Drupal\okta_import\Event\ValidateUserImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class ValidateUserImportSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ValidateUserImportEvent::OKTA_IMPORT_VALIDATEUSERIMPORT] = 'doValidateUserImport';
    return $events;
  }

  /**
   * Alter user before validate.
   *
   * @param \Drupal\okta_import\Event\ValidateUserImportEvent $event
   *   Validate Event.
   */
  public function doValidateUserImport(ValidateUserImportEvent $event) {
    // $emails = $event->getEmails();
    // ksm($emails);
  }

}
