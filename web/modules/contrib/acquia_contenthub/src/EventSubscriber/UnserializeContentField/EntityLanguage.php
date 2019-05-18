<?php

namespace Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Language and default_language handling code.
 */
class EntityLanguage implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::UNSERIALIZE_CONTENT_ENTITY_FIELD] = ['onUnserializeContentField', 100];
    return $events;
  }

  /**
   * Handles language fields and the default_langcode field values.
   *
   * This is actually a tricky bit of code that throws away language values
   * for the "langcode" entity type key when present because Drupal will do its
   * own determination of things like "default_langcode" if values are present
   * in that field. Rather than allow this, we throw it away and handle
   * defaults manually.
   *
   * @param \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent $event
   *   The unserialize event.
   */
  public function onUnserializeContentField(UnserializeCdfEntityFieldEvent $event) {
    $entityType = $event->getEntityType();
    if ($entityType->hasKey('langcode')) {
      $fieldName = $event->getFieldName();
      if ($event->getFieldMetadata()['type'] == 'boolean' && $fieldName == 'default_langcode') {
        $field = $event->getField();
        $values = [];
        foreach ($field['value'] as $langcode => $value) {
          $values[$langcode][$entityType->getKey('langcode')] = $langcode;
        }
        $event->setValue($values);
        $event->stopPropagation();
      }
    }
  }

}
