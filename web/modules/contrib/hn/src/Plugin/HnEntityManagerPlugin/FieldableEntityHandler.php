<?php

namespace Drupal\hn\Plugin\HnEntityManagerPlugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\hn\Plugin\HnEntityManagerPluginBase;

/**
 * Provides a HN Entity Handler for the FieldableEntity entity.
 *
 * @HnEntityManagerPlugin(
 *   id = "hn_fieldable_entity"
 * )
 */
class FieldableEntityHandler extends HnEntityManagerPluginBase {

  protected $supports = 'Drupal\Core\Entity\FieldableEntityInterface';

  /**
   * {@inheritdoc}
   */
  public function handle(EntityInterface $entity, $view_mode = 'default') {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    /** @var \Drupal\hn\HnResponseService $responseService */
    $responseService = \Drupal::getContainer()->get('hn.response');
    // If the current user doesn't have permission to view, don't add.
    if (!$entity->access('view', \Drupal::getContainer()->get('current_user'))) {
      $responseService->log('Not adding entity ' . $entity->uuid() . ', no permission.');
      return;
    }

    $responseService->log('[' . $entity->uuid() . '] Adding entity.');

    $entity_with_views = $responseService->entitiesWithViews->addEntity($entity, $view_mode);
    if (!$entity_with_views) {
      $responseService->log('[' . $entity->uuid() . '] View already added, not adding.');
      return;
    }

    $responseService->log('[' . $entity->uuid() . '] Getting hidden fields..');

    $hidden_fields = $entity_with_views->getHiddenFields();

    $responseService->log('[' . $entity->uuid() . '] NULLifiing hidden fields..');

    $fields_to_restore = [];

    // Nullify all hidden fields, so they aren't normalized.
    foreach ($entity->getFields() as $field_name => $field) {

      if (in_array($field_name, $hidden_fields) || !$field->access('view', \Drupal::getContainer()->get('current_user'))) {
        $fields_to_restore[$field_name] = $entity->get($field_name)->getValue();
        $entity->set($field_name, NULL);
      }

    }

    $responseService->log('[' . $entity->uuid() . '] Normalizing..');

    $normalized_entity = [
      '__hn' => [
        'view_modes' => $entity_with_views->getViewModes(),
      ],
    ] + \Drupal::getContainer()->get('serializer')->normalize($entity);

    foreach ($fields_to_restore as $field_name => $value) {
      $entity->set($field_name, $value);
    }

    $responseService->log('[' . $entity->uuid() . '] Looping trough all fields..');

    // Now completely remove the hidden fields.
    foreach ($entity->getFields() as $field_name => $field) {
      if (in_array($field_name, $hidden_fields) || !$field->access('view', \Drupal::getContainer()->get('current_user'))) {
        unset($normalized_entity[$field_name]);
        if ($responseService->isDebugging()) {
          $normalized_entity['__hn']['hidden_fields'][] = $field_name;
        }
      }

      // If this field is an entity reference, add the referenced entities too.
      elseif ($field instanceof EntityReferenceFieldItemListInterface) {

        // Get all referenced entities.
        $referenced_entities = $field->referencedEntities();

        // Get the referenced view mode (e.g. teaser) that is set in the current
        // display (e.g. full).
        $referenced_entities_display = $entity_with_views->getDisplay($view_mode)->getComponent($field_name);
        $referenced_entities_view_mode = $referenced_entities_display && $referenced_entities_display['type'] === 'entity_reference_entity_view' ? $referenced_entities_display['settings']['view_mode'] : 'default';

        foreach ($referenced_entities as $referenced_entity) {
          $responseService->addEntity($referenced_entity, $referenced_entities_view_mode);
        }

      }
    }

    return $normalized_entity;

  }

}
