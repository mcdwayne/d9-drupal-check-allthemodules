<?php

/**
 * @file
 * Contact default fields override module api file.
 */

/**
 * Alter the list of fields that are overidable.
 *
 * See \Drupal\contact\Entity\Message::baseFieldDefinitions for all
 * available options.
 *
 * @param string[] $overidable_fields
 *   The fields which are overidable.
 */
function hook_contact_default_fields_override_alter(array &$overidable_fields) {
  $overidable_fields = [
    'message',
    'name',
  ];
}
