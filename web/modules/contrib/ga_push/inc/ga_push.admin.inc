<?php

/**
 * @file
 * The administration functions and forms.
 */

/**
 * Form: GA Push settings.
 */
function ga_push_settings_form($form, &$form_state) {

  $options = ga_push_get_methods_option_list(NULL, FALSE);

  $help = [
    '#theme' => 'item_list',
    '#items' => [
      t("Universal methods are compatible with google_analytics 2.x (recommended), Classic methods are compatible with google_analytics 1.x"),
      t("Client-side: the action will be sent on the next page load - Server-side: the action will be sent immediately."),
    ],
  ];

  $form['help'] = [
    '#markup' => render($help),
  ];

  $form['ga_push_default_method'] = [
    '#type' => 'radios',
    '#title' => t('Default method'),
    '#options' => $options,
    '#default_value' => \Drupal::config('ga_push.settings')->get('default_method'),
    '#description' => t("Select the default method, it could be overriden in function call or each configuration of rules."),
    '#required' => TRUE,
  ];

  return system_settings_form($form);
}
