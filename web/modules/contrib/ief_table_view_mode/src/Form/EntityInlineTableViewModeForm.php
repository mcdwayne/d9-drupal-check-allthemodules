<?php

namespace Drupal\ief_table_view_mode\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

class EntityInlineTableViewModeForm extends EntityInlineForm {

  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);
    $entity_type = $this->entityType->id();
    $original_fields = $fields;
    $old_fields = array();
    $change_apply = FALSE;
    $entityFieldManager = \Drupal::service('entity_field.manager');

    foreach ($bundles as $bundle) {
      $display = entity_load('entity_view_display', $entity_type . '.' . $bundle . '.' . IEF_TABLE_VIEW_MODE_NAME);
      if (!$display || !($display instanceof EntityViewDisplayInterface) || !$display->status()) {
        continue;
      }

      $old_fields = $fields;
      $fields = array();

      $change_apply = TRUE;
      $field_definitions = $entityFieldManager->getFieldDefinitions($entity_type, $bundle);
      // Checking fields instances.
      foreach ($field_definitions as $field_name => $field_definition) {
        if (!$field_definition->isDisplayConfigurable('view')) {
          continue;
        }
        $display_options = $display->getComponent($field_name);
        if (empty($display_options)) {
          continue;
        }
        $fields[$field_name] = array(
          'type' => 'field',
          'label' => $field_definition->getLabel(),
          'display_options' => $display_options,
          'weight' => $display_options['weight'],
        );
      }

      // Default settings maybe has not registered any extra field.
      foreach ($old_fields as $old_field_name => $old_field) {
        if (isset($fields[$old_field_name])) {
          continue;
        }
        $display_options = $display->getComponent($old_field_name);
        if (empty($display_options)) {
          continue;
        }
        $fields[$old_field_name] = $old_field;
        $fields[$old_field_name]['weight'] = $display_options['weight'];
      }

      $old_fields = array();

      $extra_fields = $entityFieldManager->getExtraFields($entity_type, $bundle);
      $extra_fields = isset($extra_fields['display']) ? $extra_fields['display'] : array();

      foreach ($extra_fields as $extra_field_name => $extra_field) {
        $display_options = $display->getComponent($extra_field_name);
        if (empty($display_options)) {
          continue;
        }
        $fields[$extra_field_name] = array(
          'type' => 'callback',
          'label' => $extra_field['label']->render(),
          'callback' => 'ief_table_view_mode_table_field_extra_field_callback',
          'callback_arguments' => array($extra_field_name),
          'weight' => $display_options['weight'],
        );
      }
    }

    return $fields;
  }
}
