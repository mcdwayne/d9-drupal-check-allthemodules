<?php

namespace Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Entity/image/file field reference handling.
 */
class EntityReferenceField implements EventSubscriberInterface {
  use FieldEntityDependencyTrait;

  protected $fieldTypes = [
    'file',
    'entity_reference',
    'entity_reference_revisions',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::UNSERIALIZE_CONTENT_ENTITY_FIELD] = ['onUnserializeContentField', 10];
    return $events;
  }

  /**
   * Extracts the target storage and retrieves the referenced entity.
   *
   * @param \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent $event
   *   The unserialize event.
   *
   * @throws \Exception
   */
  public function onUnserializeContentField(UnserializeCdfEntityFieldEvent $event) {
    $field = $event->getField();
    if (!in_array($event->getFieldMetadata()['type'], $this->fieldTypes)) {
      return;
    }
    $values = [];
    if (!empty($field['value'])) {
      foreach ($field['value'] as $langcode => $value) {
        if (!$value) {
          $values[$langcode][$event->getFieldName()] = [];
          continue;
        }
        if (!is_array($value)) {
          $entity = $this->getEntity($value, $event);
          $values[$langcode][$event->getFieldName()] = $entity->id();
          // @todo handle single value ERR fields.
        }
        else {
          foreach ($value as $delta => $item) {
            $entity = $this->getEntity($item, $event);
            if ($event->getFieldMetadata()['type'] == 'entity_reference_revisions') {
              /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
              $values[$langcode][$event->getFieldName()][] = [
                'target_id' => $entity->id(),
                'target_revision_id' => $entity->getRevisionId(),
              ];
            }
            else {
              $values[$langcode][$event->getFieldName()][]['target_id'] = $entity->id();
            }
          }
        }
      }
    }

    $event->setValue($values);
    $event->stopPropagation();
  }

}
