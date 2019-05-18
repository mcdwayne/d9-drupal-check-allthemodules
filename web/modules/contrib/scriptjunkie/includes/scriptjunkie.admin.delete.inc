<?php
/**
 * @file
 * Administration page for deleting scripts.
 */

/**
 * Menu Callback function for delete form.
 * @param array $form_state
 * @param array $script
 * @return array
 */
function scriptjunkie_delete($form_state, $script = NULL) {
  if (empty($script)) {
    drupal_set_message(t('The specified script was not found'), 'error');
    drupal_goto('admin/settings/scriptjunkie');
  }

  $form = array();

  $form['name'] = array(
    '#type' => 'value',
    '#value' => $script['name']
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete the script "%title (Namespace: @name)"?', array('%title' => $script['general']['title'], '@name' => $script['name'])),
    'admin/settings/scriptjunkie',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission callback for scriptjunkie_delete form.
 * @param array $form
 * @param array $form_state
 */
function scriptjunkie_delete_submit($form, &$form_state) {
  $script = scriptjunkie_get_settings($form_state['values']['name']);

  scriptjunkie_delete_script($script['name']);
  drupal_set_message(t('Script "%title (Namespace: @name)" was deleted.', array('%title' => $script['general']['title'], '@name' => $script['name'])));
  $form_state['redirect'] = 'admin/settings/scriptjunkie';
}