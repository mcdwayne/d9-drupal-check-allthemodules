<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PathFieldSerializer.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\SerializeContentField
 */
class PathFieldSerializer extends FallbackFieldSerializer implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField', 5];
    return $events;
  }

  /**
   * Manipulate the path properties.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    if ('path' !== $event->getField()->getFieldDefinition()->getType()) {
      return;
    }

    parent::onSerializeContentField($event);
    $values = $event->getFieldData();
    foreach ($values['value'] as $langcode => $value) {
      // @todo check core's behavior around empty paths.
      if (!empty($value['alias'])) {
        $value['source'] = '';
        $value['pid'] = '';
        $values['value'][$langcode] = $value;
        $event->setFieldData($values);
      }
    }
  }

}
