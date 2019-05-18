<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entity field serialization to handle language reference.
 */
class LanguageFieldSerializer implements EventSubscriberInterface {

  use ContentFieldMetadataTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField', 10];
    return $events;
  }

  /**
   * Directly reference the field's value as the language.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   The content entity field serialization event.
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    if ('language' !== $event->getField()->getFieldDefinition()->getType()) {
      return;
    }
    if ($event->getFieldName() === $event->getEntity()->getEntityType()->getKey('langcode')) {
      // Do not syndicate the "langcode" entity type key because Drupal will do
      // its own determination of things like "default_langcode" if values are
      // present in that field.
      $event->setExcluded();
      $event->stopPropagation();
    }

    $this->setFieldMetaData($event);
    $data = [];
    /** @var \Drupal\Core\Entity\TranslatableInterface $entity */
    $entity = $event->getEntity();
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $field = $event->getFieldTranslation($langcode);
      foreach ($field as $item) {
        $data['value'][$langcode] = $item->getValue()['value'];
      }
    }

    $event->setFieldData($data);
    $event->stopPropagation();
  }

}
