<?php

/**
 * @file
 * Documents API functions for Drupal Swipebox module.
 */

/**
 * Extends the image style options with picture mapping options.
 *
 * @return
 *   Array of picture mappings both key and value are set to style name.
 *
 * @see \Drupal\dsbox\Plugin\Field\FieldFormatter\DrupalSwipeboxFormatter
 */
function hook_dsbox_picture_mapping() {
  $picture_options = array();
  $picture_mappings = entity_load_multiple('picture_mapping');

  if ($picture_mappings && !empty($picture_mappings)) {
    foreach ($picture_mappings as $machine_name => $picture_mapping) {
      if ($picture_mapping->hasMappings()) {
        // Provides extended keys of the picture mapping to make it possible
        // to identify such keys
        $picture_options['pm-' . $machine_name] = $picture_mapping->label();
      }
    }
  }

  return $picture_options;
}
