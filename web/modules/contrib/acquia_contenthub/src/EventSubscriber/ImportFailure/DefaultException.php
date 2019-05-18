<?php

namespace Drupal\acquia_contenthub\EventSubscriber\ImportFailure;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\FailedImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultException implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::IMPORT_FAILURE][] = 'onImportFailure';
    return $events;
  }

  /**
   * A default failure exception creator.
   *
   * @param \Drupal\acquia_contenthub\Event\FailedImportEvent $event
   *   The failure event.
   */
  public function onImportFailure(FailedImportEvent $event) {
    if (!$event->hasException()) {
      $exception = new \Exception(sprintf("Failed to import. %d of %d imported", $event->getCount(), count($event->getCdf()->getEntities())));
      $event->setException($exception);
    }
  }

}
