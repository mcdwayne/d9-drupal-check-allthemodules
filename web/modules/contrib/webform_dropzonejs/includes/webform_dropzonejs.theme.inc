<?php

/**
 * @file
 * Theme hooks, preprocessor, and suggesttions.
 */


/******************************************************************************/
// Theme hooks.
/******************************************************************************/

/**
 * Implements hook_theme().
 */
function webform_dropzonejs_theme() {
  $info = [
    'webform_element_dropzonejs' => [
      'variables' => ['element' => [], 'value' => NULL, 'webform_submission' => NULL, 'options' => [], 'file' => NULL],
      'file' => 'includes/webform_dropzonejs.theme.template.inc',
    ],
  ];

  return $info;
}
