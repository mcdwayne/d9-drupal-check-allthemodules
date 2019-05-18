<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to remove Paragraph parent id.
 */
class RemoveParagraphParentId implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = [
      'onSerializeContentField',
      101,
    ];
    return $events;
  }

  /**
   * Prevent paragraph parent_id from being added to the serialized output.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof Paragraph && $event->getFieldName() == 'parent_id') {
      $event->setExcluded();
      $event->stopPropagation();
    }
  }

}
