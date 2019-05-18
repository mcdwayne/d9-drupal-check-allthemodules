<?php

/**
 * @file
 * Hooks provided by the Rewrite Field module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the generated link data before the field is rewritten.
 *
 * This hook gets invoked for every field view that is generated.
 *
 * @param Drupal\Core\Field\FieldItemListInterface $items
 *   Object containing Field items that are to be rewritten before rendering.
 * @param array &$settings
 *   Alter settings that are added to the field.
 */
function hook_rewrite_settings_alter(Drupal\Core\Field\FieldItemListInterface $items, &$settings) {
  if ($items->getName() == 'field_rewrite_field') {
    // Your code goes here ...
  }
}

/**
 * @} End of "addtogroup hooks".
 */
