<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entity field serialization to handle basic field values.
 */
class GeneralFieldSerializer implements EventSubscriberInterface {

  use ContentFieldMetadataTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField', 10];
    return $events;
  }

  /**
   * Directly reference the field's value property.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    $acceptable_types = [
      'integer',
      'boolean',
      'string',
    ];
    if (in_array($event->getField()->getFieldDefinition()->getType(), $acceptable_types)) {
      $this->setFieldMetaData($event);
      $data = [];
      /** @var \Drupal\Core\Entity\TranslatableInterface $entity */
      $entity = $event->getEntity();
      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $field = $event->getFieldTranslation($langcode);
        foreach ($field as $item) {
          if ($field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
            $data['value'][$langcode][] = $item->getValue()['value'];
          }
          else {
            $data['value'][$langcode] = $item->getValue()['value'];
          }
        }
      }
      $event->setFieldData($data);
      $event->stopPropagation();
    }
  }

}
