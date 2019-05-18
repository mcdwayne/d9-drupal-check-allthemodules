<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\Core\Entity\TranslatableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extracts entity languages and creates a CDFAttribute.
 */
class EntityLanguagesAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES][] = ['onPopulateAttributes', 100];
    return $events;
  }

  /**
   * Documents entity languages as an attribute.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof TranslatableInterface) {
      $values = [];
      foreach ($entity->getTranslationLanguages() as $language) {
        $values[] = $language->getId();
      }
      $cdf = $event->getCdf();
      $metadata = $cdf->getMetadata();
      $metadata['languages'] = $values;
      $cdf->setMetadata($metadata);
    }
  }

}
