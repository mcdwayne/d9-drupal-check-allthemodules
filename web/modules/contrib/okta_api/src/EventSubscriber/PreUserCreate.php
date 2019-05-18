<?php

namespace Drupal\okta_api\EventSubscriber;

use Drupal\okta_api\Event\PreUserCreateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class PreUserCreate implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PreUserCreateEvent::OKTA_API_PREUSERCREATE] = 'doPreUserCreate';
    return $events;
  }

  /**
   * Alter user before create.
   *
   * @param \Drupal\okta_api\Event\PreUserCreateEvent $event
   *   Pre User Create Event.
   */
  public function doPreUserCreate(PreUserCreateEvent $event) {
    $user = $event->getUser();
    // $user['profile']['firstName'] = 'Janak';
    // $user['profile']['lastName'] = 'Singh';
    // ksm($user);
    $event->setUser($user);
  }

}
