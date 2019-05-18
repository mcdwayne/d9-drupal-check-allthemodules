Pseudo field
------------

This module allows you to render an extra field as a real field (with label).

Description
-----------

The extra fields that can be attached to the entity using
hook_field_extra_fields() lack the overall look and feel of real fields because
real fields have labels and a theme around them while extra fields initially
look pretty bare (unless you theme them manually).

Just add "#pseudo_field" property set to TRUE to the extra field output render
array and it will look just like the real field!

Note: If extra field render array has children arrays, the extra field will be
rendered as a multiple value field with each child array being an individual
field item.

Usage
-------------

1. "#pseudo_field" - if this property is set to TRUE, extra field render array
will be rendered with the "field" theme.
2. "#title" - field label for the extra field. Defaults to the label of the
extra field defined in hook_field_extra_fields().
3. "#label_display" - defines the position of the extra field label. Available
options are: "above", "inline" and "hidden". Defaults to "above" or "hidden" if
value of "#title" property is empty. (Unfortunately, field label position cannot
be set for extra fields in entity's view mode settings, so this option enables
you to set the label position as needed.)
4. "#field_name" - defines a field name which is used in "field-name-FIELDNAME"
class of the field HTML element. Defaults to the extra field key defined in
hook_field_extra_fields().
5. "#field_type" - defines the type of the field which is used in
"field-type-FIELDTYPE" class of the field HTML element. Defaults to
"extra_field".

Example
-------

<?php

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Implements hook_entity_extra_field_info().
 *
 * Adds two extra fields to view modes of "page" content type.
 */
function MODULE_entity_extra_field_info() {
  $data['node']['page']['display']['single_value'] = array(
    'label' => t('Single value extra field'),
    'weight' => 0,
  );
  $data['node']['page']['display']['multiple_values'] = array(
    'label' => t('Multiple value extra field'),
    'weight' => 1,
  );
  return $data;
}

/**
 * Implements hook_entity_view().
 *
 * Provides content for "single_value" and "multiple_values" extra fields.
 */
function MODULE_entity_view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
  if ($entity->bundle() == 'page') {
    // Make extra field look like a single value field. This render array
    // has only markup with no children arrays.
    $build['single_value'] = array(
      '#pseudo_field' => TRUE,
      '#title' => 'Cat meowing',
      '#markup' => t('Meow!'),
    );
    // Make extra field look like a multi value field. This render array
    // has children arrays, so each child array will act as a single
    // field value in a multi value field. Label position is set to "inline".
    // As #title property is not set, label of the field will be set to
    // "Multiple value extra field" (extra field definition label).
    // Wrapping div element of the extra field will have
    // "field-name-multiple-values-with-dog-sounds" class as
    // #field_name is explicitly set.
    $build['multiple_values'] = array(
      '#pseudo_field' => TRUE,
      '#label_display' => 'inline',
      '#field_name' => 'multiple_values_with_dog_sounds',
      0 => array(
        '#markup' => 'Woof woof',
      ),
      1 => array(
        '#markup' => 'Ruff ruff',
      ),
      2 => array(
        '#markup' => 'Yap yap',
      ),
    );
  }
}

?>
