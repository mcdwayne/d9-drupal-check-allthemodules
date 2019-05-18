<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entity field serialization to extract the filter format uuid.
 */
class TextItemFieldSerializer implements EventSubscriberInterface {

  use ContentFieldMetadataTrait;

  protected $fieldTypes = [
    'text_with_summary',
    'text',
    'text_long',
  ];

  /**
   * The filter format entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * TextItemFieldSerializer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Exception
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('filter_format');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField', 100];
    return $events;
  }

  /**
   * Extract filter format UUID and place it into the serialized field values.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    if (!in_array($event->getField()->getFieldDefinition()->getType(), $this->fieldTypes)) {
      return;
    }

    $this->setFieldMetaData($event);
    $data = [];
    $data['field_type'] = $event->getField()->getFieldDefinition()->getType();
    /** @var \Drupal\Core\Entity\TranslatableInterface $entity */
    $entity = $event->getEntity();
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $field = $event->getFieldTranslation($langcode);
      foreach ($field as $item) {
        $values = $item->getValue();
        if (!empty($values['format']) && $format = $this->storage->load($values['format'])) {
          $values['format'] = $format->uuid();
        }
        $data['value'][$langcode][] = $values;
      }
    }

    $event->setFieldData($data);
    $event->stopPropagation();
  }

}
