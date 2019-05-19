<?php
/**
 * @file
 * Implements views hooks.
 */

/**
 * Implements hook_views_data().
 */
function simple_modal_entity_form_views_data() {
  $data = [];
  // Registers an entity area handler per entity type.
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    // Excludes entity types, which cannot be rendered.
    if ($entity_type->hasFormClasses()) {
      $label = $entity_type->getLabel();
      $data[$entity_type->getBaseTable()][$entity_type_id . '_modal_edit'] = [
        'title' => t('Link to edit @label (Modal)', ['@label' => $label]),
        'help' => t('Provide an link to edit the @label in a modal.', ['@label' => $label]),
        'area' => [
          'entity_type' => $entity_type_id,
          'id' => 'entity',
        ],
        'field' => [
          'id' => 'modal_entity_edit_field',
        ],
      ];
      $data[$entity_type->getBaseTable()][$entity_type_id . '_modal_delete'] = [
        'title' => t('Link to delete @label (Modal)', ['@label' => $label]),
        'help' => t('Provide an link to delete the @label in a modal.', ['@label' => $label]),
        'area' => [
          'entity_type' => $entity_type_id,
          'id' => 'entity',
        ],
        'field' => [
          'id' => 'modal_entity_delete_field',
        ],
      ];
      $data[$entity_type->getBaseTable()][$entity_type_id . '_modal_view'] = [
        'title' => t('Link to view @label (Modal)', ['@label' => $label]),
        'help' => t('Provide an link to view the @label in a modal.', ['@label' => $label]),
        'area' => [
          'entity_type' => $entity_type_id,
          'id' => 'entity',
        ],
        'field' => [
          'id' => 'modal_entity_view_field',
        ],
      ];
    }
  }
  return $data;
}
