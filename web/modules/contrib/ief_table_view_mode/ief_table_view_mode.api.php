<?php

/**
 * @file
 * Hooks provided by the IEF Table View Mode.
 */

/**
 * Alter the fields already altered by ief table view mode.
 *
 * This hook is triggered if the view mode is activated.
 *
 * @param array $fields
 *  The array altered by ief table view mode.
 * @param array $original_fields
 *  The original $fields variable.
 * @param array $context
 *  The original $context variable.
 *
 * @see hook_inline_entity_form_table_fields_alter().
 */
function hook_ief_table_view_mode_fields_alter($fields, $original_fields, $context) {
  // ...
}
