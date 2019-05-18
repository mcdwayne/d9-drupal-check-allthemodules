<?php

/**
 * @file
 * Contains hooks for field_list_details module.
 */

/**
 * Allow other modules to add their own field details.
 *
 * @param \Drupal\field_list_details\FieldListDetailsCollection $collection
 *   The details collection for a specific field.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 *   The field that is being examined for details.
 */
function hook_field_list_details_alter(\Drupal\field_list_details\FieldListDetailsCollection $collection, \Drupal\Core\Field\FieldDefinitionInterface $field) {
  $settings = $field->getThirdPartySettings('some_third_party_module_name');

  if (!empty($settings['my_setting'])) {
    $collection->setDetail('my_setting', t('My Value'), t('My Label'));
  }
}
