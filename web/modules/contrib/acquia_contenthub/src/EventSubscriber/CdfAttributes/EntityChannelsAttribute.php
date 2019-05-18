<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Acquia\ContentHubClient\CDFAttribute;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notates the metadata with a CDF version marker.
 */
class EntityChannelsAttribute implements EventSubscriberInterface {

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
    $cdf = $event->getCdf();
    $cdf->addAttribute('channels', CDFAttribute::TYPE_ARRAY_REFERENCE, []);
    $cdf->getAttribute('channels')->setValue([]);
  }

}
