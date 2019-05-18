<?php

/**
 * @file
 * Post update functions for Cached Computed Field.
 */

/**
 * Configure the batch size for processing expired items.
 */
function cached_computed_field_post_update_set_batch_size_option() {
  // In new installations the new batch_size option defaults to 20 items but we
  // set this to 1 for existing installations to maintain backwards
  // compatibility.
  \Drupal::configFactory()->getEditable('cached_computed_field.settings')
    ->set('batch_size', 1)
    ->save();
}

/**
 * Remove the obsolete item limit configuration.
 */
function cached_computed_field_post_update_remove_item_limit() {
  \Drupal::configFactory()->getEditable('cached_computed_field.settings')
    ->clear('item_limit')
    ->save();
}
