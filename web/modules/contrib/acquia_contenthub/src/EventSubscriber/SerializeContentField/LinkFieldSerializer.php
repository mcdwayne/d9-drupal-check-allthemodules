<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Link Field Serializer.
 *
 * This class handles the serialization of menu_link entities.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\SerializeContentField
 */
class LinkFieldSerializer implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField', 15];
    return $events;
  }

  /**
   * On serialize content field event function.
   *
   * Extracts entity uuids from link fields and serializes them.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   *
   * @throws \Exception
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    // Return if the type of field is not a link.
    if ($event->getField()->getFieldDefinition()->getType() != 'link') {
      return;
    }

    // Get main entity.
    $entity = $event->getEntity();

    // Confirm the entity is an instance of ContentEntityInterface.
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }
    $fields = $this->getFieldsFromEntity($entity);
    if (!$fields) {
      return;
    }

    $cdf = $event->getCdf();
    $metadata = $cdf->getMetadata();
    // Init data arr.
    $data = [];

    // Loop through entity fields.
    foreach ($fields as $field) {
      $langcode = $field->getLangcode();

      // Set type in meta data.
      $metadata['field'][$event->getFieldName()] = [
        'type' => $event->getField()->getFieldDefinition()->getType(),
      ];

      // Loop through fields to get link values to serialize.
      foreach ($field as $item) {
        // Get values.
        $values = $item->getValue();

        // If values are empty, continue to next menu_link item.
        if (empty($values['uri'])) {
          continue;
        }

        // Explode the uri first by a colon to retrieve the link type.
        list($uri_type, $uri_reference) = explode(':', $values['uri'], 2);

        // Set uri type in meta data.
        $values['uri_type'] = $item->isExternal() ? 'external' : $uri_type;
        if ($uri_type === 'entity') {
          // Explode entity to get the type and id.
          list($entity_type, $entity_id) = explode('/', $uri_reference, 2);

          // Load the entity to be added as a dependency.
          $uri_entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);

          // Place the entity's uuid into the value position.
          $values['uri'] = $uri_entity->uuid();
        }
        $data['value'][$langcode][] = $values;
      }
    }

    // Set data before continuing.
    $event->setFieldData($data);
    // Set the meta data.
    $cdf->setMetadata($metadata);
    // Stop event propagation.
    $event->stopPropagation();
  }

  /**
   * Extracts all fields in all translations that match our criteria.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   List of fields.
   */
  protected function getFieldsFromEntity(ContentEntityInterface $entity) {
    $fields = [];
    $languages = $entity->getTranslationLanguages();

    /* @var \Drupal\Core\Field\FieldItemListInterface $field */
    foreach ($entity as $field_name => $field) {
      // Check if field definition type is a link.
      if ($field->getFieldDefinition()->getType() === 'link') {
        // If the field is translatable get all translations of it.
        if ($field->getFieldDefinition()->isTranslatable()) {
          foreach ($languages as $language) {
            $translated = $entity->getTranslation($language->getId());
            $fields[] = $translated->get($field_name);
          }
        }
        else {
          $fields[] = $field;
        }
      }
    }
    return $fields;
  }

}
