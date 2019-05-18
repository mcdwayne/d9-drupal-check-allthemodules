<?php

namespace Drupal\drupal_content_sync_views\EventSubscriber;

use Drupal\Core\Field\FieldStorageDefinitionEvent;
use Drupal\Core\Field\FieldStorageDefinitionEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * React on field storage changes.
 */
class FieldStorageSubscriber implements EventSubscriberInterface {

  /**
   * If data for the dcs meta entities already exists, it gets migrated
   * to the dynamic reference field.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionEvent $event
   *   The entity storage object.
   */
  public function onCreate(FieldStorageDefinitionEvent $event) {

    // Ensure to only react when the the entity field provided by this module
    // is added.
    $field_storage_definition = $event->getFieldStorageDefinition();
    $field_name = $field_storage_definition->getName();
    $provider = $field_storage_definition->getProvider();
    if ($field_name == 'entity' && $provider == 'drupal_content_sync_views') {
      $meta_entities = \Drupal::entityQuery('dcs_meta_info')->execute();
      if (!empty($meta_entities)) {
        $meta_entity_storage = $node_storage = \Drupal::entityTypeManager()->getStorage('dcs_meta_info');
        foreach ($meta_entities as $entity_id) {
          $meta_info_entity = $meta_entity_storage->load($entity_id);
          $referenced_entity = \Drupal::service('entity.repository')
            ->loadEntityByUuid($meta_info_entity->get('entity_type')->value, $meta_info_entity->get('entity_uuid')->value);
          $meta_info_entity->set('entity', $referenced_entity);
          $meta_info_entity->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FieldStorageDefinitionEvents::CREATE][] = ['onCreate'];
    return $events;
  }

}
