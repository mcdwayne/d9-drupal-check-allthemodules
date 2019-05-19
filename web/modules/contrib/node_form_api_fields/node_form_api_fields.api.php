<?php

/**
 * @file
 * Hooks for node_form_api_fields module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow other modules to add fields to a node via the Form API.
 *
 * This hook lets modules add fields to a node form via the Form API
 * and handles storing those fields automatically using the Drupal 8
 * Key Value storage.
 *
 * @param array $form
 *   The variable for you to put your Form API fields.
 * @param array $defaults
 *   Any default values already saved for your fields. If #default_value
 *   is not set by you, it will be automatically set.
 * @param array $context
 *   An array of contextual data containing:
 *   - form
 *   - form_state
 *   - form_id
 *   - node object
 *   - node nid.
 *
 * @link https://api.drupal.org/api/drupal/elements
 *   The Drupal 8 Form API.
 */
function hook_node_form_api_fields_form_alter(&$form, $defaults, $context) {

  $form['form_api_fields'] = [
    '#type' => 'textfield',
    '#title' => t('Extra Field'),
    '#size' => 60,
    '#maxlength' => 128,
    '#required' => TRUE,
  ];

}

/**
* @} End of "addtogroup hooks".
*/
