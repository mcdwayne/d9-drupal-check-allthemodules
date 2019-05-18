<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Password Field Serializer.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\SerializeContentField
 */
class PasswordFieldSerializer extends FallbackFieldSerializer implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = [
      'onSerializeContentField',
      5,
    ];
    return $events;
  }

  /**
   * Manipulate the password properties.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    if ($event->getField()->getFieldDefinition()->getType() == 'password') {
      parent::onSerializeContentField($event);
      if ($values = $event->getFieldData()) {
        foreach ($values['value'] as $langcode => $value) {
          $values['value'][$langcode]['pre_hashed'] = TRUE;
        }
        $event->setFieldData($values);
        $event->stopPropagation();
      }
    }

  }

}
