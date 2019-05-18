<?php

/**
 * @file
 * Hooks related to Type Style module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the form used by Type Style to configure styles.
 *
 * Note: If you're adding a new style, you'll also need to define its schema.
 * See config/schema/type_style.schema.yml for reference.
 *
 * @param array &$form
 *   The full type form. Add new styles to $form['type_style'].
 * @param \Drupal\Core\Config\Entity\ConfigEntityBundleBase $type
 *   The type being edited.
 */
function hook_type_style_form_alter(array &$form, \Drupal\Core\Config\Entity\ConfigEntityBundleBase $type) {
  $label = $type->getEntityType()->getLabel();
  $settings = $type->getThirdPartySettings('type_style');

  $form['type_style']['secondary_color'] = [
    '#type' => 'color',
    '#title' => t('Secondary color'),
    '#description' => t('The secondary color for this @label', ['@label' => $label]),
    '#default_value' => isset($settings['secondary_color']) ? $settings['secondary_color'] : '',
  ];
  /** @see type_style_entity_builder() for reference on how to set values. */
  $form['#entity_builders'][] = 'your_module_type_style_entity_builder';
}

/**
 * Give entities Type Style support that would not normally have it.
 *
 * Note: You should add schema for each new entity type.
 * See config/schema/type_style.schema.yml for reference.
 *
 * @return string|array
 *   An array of Entity Type IDs or a single Entity Type ID.
 */
function hook_type_style_entity_support() {
  return ['moderation_state', 'moderation_state_transition'];
}
