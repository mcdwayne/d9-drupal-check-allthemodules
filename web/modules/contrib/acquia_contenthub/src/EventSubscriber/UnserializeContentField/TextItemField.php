<?php

namespace Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manual handling for text item fields filter references.
 */
class TextItemField implements EventSubscriberInterface {
  use FieldEntityDependencyTrait;

  protected $fieldTypes = ['text_with_summary', 'text', 'text_long'];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::UNSERIALIZE_CONTENT_ENTITY_FIELD] = ['onUnserializeContentField', 100];
    return $events;
  }

  /**
   * Extract the stored filter_format uuid and retrieve the entity id.
   *
   * @param \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent $event
   *   The unserialize event.
   */
  public function onUnserializeContentField(UnserializeCdfEntityFieldEvent $event) {
    if (!in_array($event->getFieldMetadata()['type'], $this->fieldTypes)) {
      return;
    }

    $field = $event->getField();
    $values = [];

    // Return early if no attr values are set.
    if (empty($field['value'])) {
      return;
    }

    foreach ($field['value'] as $langcode => $value) {
      foreach ($value as $delta => &$item) {
        if (!$item['format']) {
          continue;
        }
        $filter = $this->getEntity($item['format'], $event);
        $item['format'] = $filter->id();
      }
      $values[$langcode][$event->getFieldName()] = $value;
    }
    $event->setValue($values);
    $event->stopPropagation();
  }

}
