<?php

namespace Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Link Field Unserializer.
 *
 * This class handles the unserialization of menu_link entities.
 */
class LinkFieldUnserializer implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::UNSERIALIZE_CONTENT_ENTITY_FIELD] = ['onUnserializeContentField', 10];
    return $events;
  }

  /**
   * On unserialize field event function.
   *
   * Handles the unserialization of menu_link entities.
   *
   * @param \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent $event
   *   The unserialize event.
   */
  public function onUnserializeContentField(UnserializeCdfEntityFieldEvent $event) {
    // Get field meta data.
    $meta = $event->getFieldMetadata();

    // Make sure the field type is link.
    if ($meta['type'] != 'link') {
      return;
    }

    // Get field and init values array to set later.
    $field = $event->getField();
    $values = [];

    // Return early if no attr values are set.
    if (empty($field['value'])) {
      return;
    }

    // Loop through field values.
    foreach ($field['value'] as $langcode => $fieldValues) {
      foreach ($fieldValues as $value) {
        if ($value['uri_type'] === 'entity') {
          // Get the entity from event stack.
          $uuid = $value['uri'];
          $uri_entity = $event->getStack()->getDependency($uuid)->getEntity();

          // Construct the entity link.
          // Format: entity:<ENT_TYPE>/<ENT_ID>.
          $entity_link = "entity:{$uri_entity->getEntityTypeId()}/{$uri_entity->id()}";

          // Set entity link as target.
          $value['uri'] = $entity_link;
        }
        unset($value['uri_type']);
        $values[$langcode][$event->getFieldName()][] = $value;
      }
    }
    // Set updated event values.
    $event->setValue($values);
    $event->stopPropagation();
  }

}
