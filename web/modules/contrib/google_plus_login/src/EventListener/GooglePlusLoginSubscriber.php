<?php

namespace Drupal\google_plus_login\EventListener;

use Drupal\google_plus_login\Event\GooglePlusLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GooglePlusLoginSubscriber implements EventSubscriberInterface {

  /**
   * Update the user fields after they log in through Google.
   *
   * @param GooglePlusLoginEvent $event
   */
  public function onUserLogin(GooglePlusLoginEvent $event) {
    $account = $event->getAccount();

    if ($account->isNew()) {
      return; // bail, only update existing accounts.
    }

    $googleUser = $event->getGoogleUser();

    // Ensure the email address is updated if changed in Google.
    if ($account->getEmail() !== $googleUser->getEmail()) {
      $account
        ->setEmail($googleUser->getEmail())
        ->save();
    }
  }

  public static function getSubscribedEvents() {
    return [
      GooglePlusLoginEvent::NAME => ['onUserLogin'],
    ];
  }

}
