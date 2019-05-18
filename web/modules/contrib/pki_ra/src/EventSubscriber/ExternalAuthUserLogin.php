<?php

namespace Drupal\pki_ra\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\pki_ra\Processors\PKIRARegistrationProcessor;
use Drupal\externalauth\Event\ExternalAuthLoginEvent;

/**
 * Subscribe to External Auth login event.
 */
class ExternalAuthUserLogin implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ExternalAuthEvents::LOGIN][] = array('checkForUserLogin');
    return $events;
  }

  /**
   * Event subscriber function.
   *
   * @param ExternalAuthLoginEvent $event
   *   Event object.
   */
  public function checkForUserLogin(ExternalAuthLoginEvent $event) {
    $account = $event->getAccount();
    if (is_object($account)) {
      $registration = PKIRARegistrationProcessor::getRegistrationByTitle($account->get('mail')->value);
      if (!empty($registration)) {
        $table_data = [
          'registration_id' => $registration->id(),
          'eoi_method' => 'certificate',
          'status' => 'Complete',
          'updated' => \Drupal::time()->getRequestTime(),
        ];
        // Update data to eoi progress table.
        $progress_manager = \Drupal::service('pki_ra.eoi_progress_manager');
        $progress_manager->userEoiSourceProgress($table_data);
      }
    }
  }

}
