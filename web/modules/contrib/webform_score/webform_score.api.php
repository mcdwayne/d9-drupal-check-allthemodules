<?php

/**
 * @file
 * Hooks related to webform_score module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information about discovered WebformScore plugins.
 *
 * @param array $definitions
 *   The array of discovered WebformScore plugins, keyed on the
 *   machine-readable score name.
 */
function hook_webform_score_info_alter(array &$definitions) {
  if (isset($definitions['some_plugin_i_want_to_alter'])) {
    $definitions['some_plugin_i_want_to_alter']['compatible_data_types'][] = 'one_more_data_type';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
