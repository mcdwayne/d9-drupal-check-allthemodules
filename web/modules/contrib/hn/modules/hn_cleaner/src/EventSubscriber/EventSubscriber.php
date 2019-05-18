<?php

namespace Drupal\hn_cleaner\EventSubscriber;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\hn\Event\HnEntityEvent;
use Drupal\hn\Event\HnHandledEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DefaultSubscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    return [
      HnEntityEvent::ADDED => 'nullifyEntityProperties',
      HnHandledEntityEvent::POST_HANDLE => 'unsetEntityProperties',
    ];

  }

  /**
   * This nullifies entity properties before they are normalized.
   *
   * This improves performance, because these properties don't need to be
   * handled by the normalizer.
   *
   * @param \Drupal\hn\Event\HnEntityEvent $event
   *   The event.
   */
  public function nullifyEntityProperties(HnEntityEvent $event) {

    $entity = $event->getEntity();
    $entity_type = $entity->getEntityTypeId();

    $config = \Drupal::config('hn_cleaner.settings');
    $removed_entities = $config->get('entities');

    if (in_array($entity_type, $removed_entities)) {
      $event->setEntity(new NullEntity([], ''));
      return;
    }

    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $removed_properties = $config->get('fields.' . $entity->getEntityTypeId());

    if (!empty($removed_properties)) {
      $entityDefinition = \Drupal::entityTypeManager()->getDefinition($entity->getEntityTypeId());
      $entityKeys = $entityDefinition->getKeys();
      $removed_properties = array_diff($removed_properties, $entityKeys);
      foreach ($removed_properties as $removed_property) {
        if ($entity->hasField($removed_property)) {
          $entity->set($removed_property, NULL);
        }
      }
    }

  }

  /**
   * This removes entity properties after they are normalized.
   *
   * @param \Drupal\hn\Event\HnHandledEntityEvent $event
   *   The event.
   */
  public function unsetEntityProperties(HnHandledEntityEvent $event) {

    $entity = $event->getEntity();
    $handledEntity = $event->getHandledEntity();

    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $config = \Drupal::config('hn_cleaner.settings');

    $removed_properties = $config->get('fields.' . $entity->getEntityTypeId());

    if (!empty($removed_properties)) {
      foreach ($removed_properties as $removed_property) {
        unset($handledEntity[$removed_property]);
      }
    }

    $event->setHandledEntity($handledEntity);
  }

}

/**
 * Entity without content.
 *
 * Can be used to replace an entity with NULL.
 */
class NullEntity extends Entity {

}
