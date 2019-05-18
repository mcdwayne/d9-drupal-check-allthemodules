<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDFAttribute;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extracts user specific data for identifying duplicate and anonymous users.
 */
class UserDataCdfAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES][] = ['onPopulateAttributes', 100];
    return $events;
  }

  /**
   * Method called on the POPULATE_CDF_ATTRIBUTES event.
   *
   * Extracts user specific data for identifying duplicate and anonymous users.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   The CdfAttributesEvent object.
   *
   * @throws \Exception
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() !== 'user') {
      return;
    }

    /** @var \Drupal\user\Entity\User $entity */
    $cdf = $event->getCdf();
    $cdf->addAttribute('username', CDFAttribute::TYPE_STRING, $entity->label());
    if ($entity->isAnonymous()) {
      $cdf->addAttribute('is_anonymous', CDFAttribute::TYPE_BOOLEAN, TRUE);
    }
    elseif ($entity->getEmail()) {
      $cdf->addAttribute('mail', CDFAttribute::TYPE_STRING, $entity->getEmail());
    }
  }

}
