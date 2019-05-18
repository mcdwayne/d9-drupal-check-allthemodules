<?php

/**
 * @file
 * Views hooks.
 */

/**
 * Implements hook_field_views_data_alter().
 */
function fraction_field_views_data_alter(&$data, $field) {

  // Only operate on fields defined by the Fraction module.
  if ($field->getTypeProvider() != 'fraction') {
    return;
  }

  // Get the field name.
  $field_name = $field->getName();

  // Get the entity type that this field is attached to.
  $entity_type = $field->getTargetEntityTypeId();

  // Look up the field's most used instance label.
  list($field_label, $other_labels) = views_entity_field_label($entity_type, $field_name);

  // Build a list of additional fields for the sort and filter handlers.
  $additional_fields = array(
    'numerator' => $field_name . '_numerator',
    'denominator' => $field_name . '_denominator',
  );

  // Iterate through the results.
  foreach ($data as $table_alias => &$definition) {

    // We will want to alter Views data for both the current field definition,
    // and the field revision definition. The current field definition will be
    // in $definition[$field_name], while the field revision definiton will be
    // in $definition[$field_name . '-revision_id']. So we need to test for the
    // presence of both and act accordingly. If neither exists, stop here and
    // skip to the next definition.
    /**
     * @see views_field_default_views_data()
     */
    $field_revision_name = $field_name . '-revision_id';
    if (empty($definition[$field_name]) && empty($definition[$field_revision_name])) {
      continue;
    }
    elseif (!empty($definition[$field_name . '-revision_id'])) {
      $field_name = $field_revision_name;
    }

    // Override the field handler so that we can provide our own custom
    // click sort method (that uses the fraction's decimal equivalent).
    $definition[$field_name]['field']['id'] = 'fraction_field';

    // Make the field click-sortable.
    $definition[$field_name]['field']['click sortable'] = TRUE;

    // Create a new decimal column with custom sort and filter handlers.
    $column_name = $field_name . '_decimal';
    $definition[$column_name] = array(
      'group' => $definition[$field_name]['group']->getUntranslatedString(),
      'title' => t('@field_label (decimal)', [
        '@field_label' => $field_label,
      ]),
      'title short' => t('@field_label:decimal', [
        '@field_label' => $field_label,
      ]),
      'help' => t('Decimal equivalent of Fraction field for sorting and filtering. @extra', [
        '@extra' => $definition[$field_name]['help'],
      ]),
      'sort' => array(
        'id' => 'fraction',
        'additional fields' => $additional_fields,
      ),
      'filter' => array(
        'id' => 'fraction',
        'additional fields' => $additional_fields,
      )
    );
  }
}
