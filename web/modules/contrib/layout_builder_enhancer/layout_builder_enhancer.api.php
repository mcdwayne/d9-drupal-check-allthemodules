<?php

/**
 * @file
 * Api.php for layout_builder_enhancer.
 */

/**
 * Tell the module which keys are allowed to use in the layout builder.
 *
 * @return array
 *   An array of keys.
 */
function hook_layout_builder_enhancer_allowed_block_keys() {
  // Allow custom blocks to be added to layout builder.
  return [
    (string) t('Custom'),
  ];
}

/**
 * Alter the allowed keys.
 *
 * @param array $keys
 *   The keys that modules have specified.
 */
function hook_layout_builder_enhancer_allowed_block_keys_alter(array &$keys) {
  // Unset some keys that another module has allowed.
  foreach ($keys as $delta => $key) {
    if ($key == (string) t('Custom')) {
      unset($keys[$delta]);
    }
  }
}

/**
 * Alter the controller result, after the layout builder has altered it.
 */
function hook_layout_builder_enhancer_chooser_result(array &$result) {
  $result[(string) t('Custom')]['#access'] = TRUE;
}

/**
 * Tell the module which layouts are allowed to use.
 */
function hook_layout_builder_enhancer_allowed_layouts() {
  return [
    'layout_onecol',
  ];
}

/**
 * Alter the keys allowed.
 *
 * @param array $keys
 *   The keys currently allowed.
 */
function hook_layout_builder_enhancer_allowed_layouts_alter(array &$keys) {
  // Unset some keys that another module has allowed.
  foreach ($keys as $delta => $key) {
    if ($key == 'layout_onecol') {
      unset($keys[$delta]);
    }
  }
}
