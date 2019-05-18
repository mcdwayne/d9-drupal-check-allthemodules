<?php

namespace Drupal\okta_api\EventSubscriber;

use Drupal\okta_api\Event\PostUserCreateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 */
class PostUserCreate implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PostUserCreateEvent::OKTA_API_POSTUSERCREATE] = 'doPostUserCreateSub';
    return $events;
  }

  /**
   * Alter user before post submit.
   *
   * @param \Drupal\okta_api\Event\PostUserCreateEvent $event
   *   Post User Create Event.
   */
  public function doPostUserCreateSub(PostUserCreateEvent $event) {
    // $user = $event->getUser();
    // ksm($user);
    // $event->setUser($user);
  }

}
