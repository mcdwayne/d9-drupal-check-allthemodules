<?php

/**
 * @file
 * Describe hooks provided by the Peytz Mail module.
 */

/**
 * Subscribe form modification hook.
 *
 * This is an example of adding a gender select box to the sign up form, but
 * only for a signup form connected to the mailinglist with
 * newsletter_machine_name value 'test_mailinglist'.
 *
 * @param array $configuration
 *   Peytz-mail block configuration information passed into the plugin.
 *
 * @return array
 *   Custom form fields.
 */
function hook_peytz_mail_form_fields(array $configuration = []) {
  // Validate against mailing list ids to determine whether or not
  // to add custom form field.
  $form = [];
  if (!empty($newsletter_list) && array_key_exists('test_mailinglist', $newsletter_list)) {
    $form['peytz_mail_custom_field_gender'] = [
      '#type' => 'select',
      '#title' => t('Gender'),
      '#multiple' => FALSE,
      '#options' => [
        'male' => t('Man'),
        'female' => t('Woman'),
      ],
    ];
  }
  return $form;
}
