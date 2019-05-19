<?php

/**
 * @file
 * Hooks related to Token replace AJAX.
 */

/**
 * Allows modules to retrieve their entity form the provided form/form_state.
 *
 * @param string $entity_type
 *   The entity type of the token being processed.
 * @param array $form
 *   The form posted to Token replace AJAX callback.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The processed form state of the Token replace AJAX form submission.
 *
 * @return bool|object
 *   The entity if found, otherwise FALSE.
 */
function hook_token_replace_ajax_form_entity($entity_type, $form, \Drupal\Core\Form\FormStateInterface $form_state) {
  if (isset($form['#my_entity'])) {
    $my_entity = (object) array_merge((array) $form_state['my_entity'], isset($form_state['values']['my_entity']) ? $form_state['values']['my_entity'] : []);

    return $my_entity;
  }

  return FALSE;
}

/**
 * Allows modules to alter the response value of the Token replace AJAX call.
 *
 * @param string $value
 *   The processed token result.
 * @param string $token
 *   The unprocessed token.
 * @param array $data
 *   The data used for the token replacement.
 */
function hook_token_replace_ajax_response_alter(&$value, $token, $data) {
  if ($value == $token) {
    $value = '';
  }
}
