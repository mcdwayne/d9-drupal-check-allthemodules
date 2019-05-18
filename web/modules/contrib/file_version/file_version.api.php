<?php

/**
 * @file
 * Hooks related to file_version.
 */

/**
 * Modify invalid_params.
 *
 * If you have some query parameter restrictions you can add here to validate
 * in settings form.
 *
 * @param array $invalid_params
 *   Array with current invalid query params to alter.
 *
 * @see \Drupal\file_version\FileVersion
 */
function hook_file_version_invalid_params_alter(array &$invalid_params) {
  $invalid_params[] = 'my_propietary_param_name';
}
