<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entity field serialization to handle entity references.
 */
class EntityReferenceFieldSerializer implements EventSubscriberInterface {

  protected $fieldTypes = [
    'file',
    'entity_reference',
    'entity_reference_revisions',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField', 100];
    return $events;
  }

  /**
   * Extract entity uuids as field values.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   *
   * @throws \Exception
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    if (!in_array($event->getField()->getFieldDefinition()->getType(), $this->fieldTypes)) {
      return;
    }

    $cdf = $event->getCdf();
    $metadata = $cdf->getMetadata();
    $metadata['field'][$event->getFieldName()] = [
      'type' => $event->getField()->getFieldDefinition()->getType(),
      'target' => $event->getField()->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type'),
    ];
    $cdf->setMetadata($metadata);
    $data = [];
    /** @var \Drupal\Core\Entity\TranslatableInterface $entity */
    $entity = $event->getEntity();
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $field = $event->getFieldTranslation($langcode);
      if ($field->isEmpty()) {
        $data['value'][$langcode] = [];
        continue;
      }
      if ($event->getFieldName() != $event->getEntity()->getEntityType()->getKey('bundle')) {
        foreach ($field as $item) {
          if (!$item->entity) {
            $entity = \Drupal::entityTypeManager()->getStorage($event->getField()->getFieldDefinition()->getSetting('target_type'))->load($item->getValue()['target_id']);
            if (is_null($entity)) {
              continue;
            }
            $item->entity = $entity;
          }
          $data['value'][$langcode][] = $item->entity->uuid();
        }
      }
      else {
        foreach ($field as $item) {
          $data['value'][$langcode] = $item->entity->uuid();
        }
      }
    }

    $event->setFieldData($data);
    $event->stopPropagation();
  }

}
