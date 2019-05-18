<?php

/**
 * @file
 * Utility functions for Group Node Field.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Returns a custom service used to handle group reference fields on nodes.
 *
 * If you need the $form and $form_state use this helper function.
 *
 * @param array $form
 *   The current node form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The state of the current node form.
 *
 * @return mixed
 *   The GroupNodeFieldService.
 */
function gnode_field_service(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\gnode_field\Service\GroupNodeFieldService $gnodeField */
  $gnodeField = \Drupal::service('gnode_field.node_group_ref');
  $gnodeField->setFormData($form, $form_state);
  $gnodeField->validateEntity();
  return $gnodeField;
}

/**
 * Helper function that performs group operations during a node operation.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The node.
 * @param string $op
 *   The current node operation being performed.
 */
function gnode_field_node_operations(EntityInterface $entity, $op) {
  /** @var \Drupal\gnode_field\Service\GroupNodeFieldService $gnodeField */
  $gnodeField = \Drupal::service('gnode_field.node_group_ref');
  $gnodeField->setEntity($entity);
  $gnodeField->nodeOperations($op);
}

/**
 * Creates a group reference fields on content types.
 *
 * This will create a field group if it does not exist and place the field in
 * that group. It will place each subsequent field in that group as well.
 *
 * @param array $form
 *   The node type edit or add form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 */
function gnode_field_add_group_reference_field(array $form, FormStateInterface $form_state) {
  /** @var \Drupal\gnode_field\Service\GroupNodeFieldService $gnodeField */
  $gnodeField = gnode_field_service($form, $form_state);
  $group_types = $gnodeField->groupTypes();

  /** @var Drupal\group\Entity\GroupType $group_type */
  foreach ($group_types as $group_type) {
    $group_type_bundle = $group_type->id();
    $group_type_label = $group_type->label();
    $field_name = $gnodeField->getGroupRef($group_type_bundle);

    if ($form_state->getValue($field_name)) {

      // The bundle of the node type we are currently editing.
      $node_type_bundle = $form_state->getValue('type');

      // This will be null if the field storage for this field does not exist.
      $field_storage = FieldStorageConfig::loadByName('node', $field_name);
      if (!$field_storage) {
        // Create the field storage for this field.
        FieldStorageConfig::create([
          'field_name' => $field_name,
          'entity_type' => 'node',
          'type' => 'entity_reference',
          'settings' => ['target_type' => 'group'],
          'cardinality' => -1,
        ])->save();
      }

      // Check for this field on the node type.
      $field = FieldConfig::loadByName('node', $node_type_bundle, $field_name);
      if (empty($field)) {
        // Create the field on this node type.
        FieldConfig::create([
          'field_name' => $field_name,
          'entity_type' => 'node',
          'bundle' => $node_type_bundle,
          'label' => $group_type_label,
          'description' => t('Only @group_type_label that are referenced will have access to this content.', ['@group_type_label' => $group_type_label . 's']),
          'settings' => [
            'handler_settings' => [
              'target_bundles' => [
                $group_type_bundle => $group_type_bundle,
              ],
            ],
          ],
        ])->save();

        // Load the form display.
        $entity_form_display = EntityFormDisplay::load("node.$node_type_bundle.default");

        // Ensure it's enabled.
        $entity_form_display->enable();

        // Enable/Show this field on the form.
        $entity_form_display->setComponent($field_name, [
          'type' => 'chosen_select',
          'weight' => 100,
        ])->save();

        // Load the entity display.
        $entity_view_display = EntityViewDisplay::load("node.$node_type_bundle.default");

        // Assign display settings for the 'default' view mode.
        $entity_view_display->setComponent($field_name, [
          // Hide the label.
          'label' => 'hidden',
        ])->save();
      }
    }
  }
}
