<?php

/**
 * @file
 * Hooks for the cg_payment module.
 */

/**
 * Validate the terminal ID and mid numbers when saving an entity with those
 * fields.
 *
 * You may implement that hook, and replace the field names with the real field
 * names from your entity (supports all entity types).
 *
 * @see hook_entity_bundle_field_info_alter()
 */
function hook_entity_bundle_field_info_alter(&$fields, EntityTypeInterface $entity_type, $bundle) {
  // Replace "field_tid" with the real terminal ID field name.
  // Replace "field_mid" with the real mid field name.
  if (!empty($fields['field_tid']) && !empty($fields['field_mid'])) {
    $fields['field_tid']->addConstraint('ValidTerminal', [
      'terminal_id_field_name' => 'field_tid',
      'mid_field_name' => 'field_mid',
    ]);
  }
}
