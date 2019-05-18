<?php

namespace Drupal\acquia_contenthub\EventSubscriber\Cdf;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\ParseCdfEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prevent user name conflicts.
 */
class ExistingUser implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::PARSE_CDF][] = ['onParseCdf', 90];
    return $events;
  }

  /**
   * Parses the CDF representation of Content Entities.
   *
   * @param \Drupal\acquia_contenthub\Event\ParseCdfEntityEvent $event
   *   Event object.
   */
  public function onParseCdf(ParseCdfEntityEvent $event) {
    $cdf = $event->getCDF();
    // Bail early if this isn't a user entity.
    if ($cdf->getAttribute('entity_type')->getValue()['und'] !== 'user') {
      return;
    }

    $username = $cdf->getAttribute('username')->getValue()['und'];
    /** @var \Drupal\user\UserInterface $account */
    $account = user_load_by_name($username);
    if (!$account) {
      // No local user by that name, proceed.
      return;
    }
    if ($account->uuid() === $event->getEntity()->uuid()) {
      // If the uuids are the same, these are the same user.
      return;
    }
    if ($account->getEmail() !== $event->getEntity()->getEmail()) {
      /** @var \Drupal\user\Entity\User $entity */
      $entity = $event->getEntity();
      $entity->setUsername($cdf->getUuid() . ' (' . $username . ')');
      $event->setEntity($entity);
    }
  }

}
