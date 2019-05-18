<?php

/**
 * @file
 * API documentation for Dirty Form.
 */

/**
 * Alter the configuration object passed to jQuery.areYouSure().
 *
 * @param array $conf
 *   Array passed as JSON to jQuery.areYouSure().
 * @param array $form
 *   The form API array.
 */
function hook_dirtyform_config_alter(array &$conf, array &$form) {
  if ($form['#form_id'] == 'webform_client_form_1424') {
    $conf['silent'] = TRUE;
  }
}
