<?php

/**
 * @file
 * Translate set pages file.
 */

/**
 * Page callback: configure the languages per user for Translate set.
 */
function translate_set_configure_languages_per_user($form, $form_state, $account) {
  $languages = language_list();
  unset($languages['en']);

  if (empty($languages)) {
    $form['info'] = array(
      '#type' => 'markup',
      '#markup' => '<p>' . t('No languages found to translate to.') . '</p>',
    );
  }
  else {

    $language_options = array();
    foreach ($languages as $key => $info) {
      $language_options[$key] = $info->name . ' (' . $info->language . ')';
    }

    $user_languages = db_select('locales_translate_set_user_languages', 'ltsul')
      ->fields('ltsul', array('language'))
      ->condition('uid', $account->uid)
      ->execute()
      ->fetchCol();

    $form['languages'] = array(
      '#type' => 'checkboxes',
      '#options' => $language_options,
      '#default_value' => $user_languages,
      '#description' => t('Select languages this user can translate on the Translate set interface. Toggle nothing if you want to allow all.')
    );

    $form['uid'] = array(
      '#type' => 'value',
      '#value' => $account->uid,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

  }

  return $form;
}

/**
 * Submit callback: saves the languages per user for Translate set.
 */
function translate_set_configure_languages_per_user_submit($form, $form_state) {
  db_delete('locales_translate_set_user_languages')
    ->condition('uid', $form_state['values']['uid'])
    ->execute();

  foreach ($form_state['values']['languages'] as $key => $value) {
    if ($key === $value) {
      db_insert('locales_translate_set_user_languages')
        ->fields(array('uid', 'language'), array($form_state['values']['uid'],$value))
        ->execute();
    }
  }

  drupal_set_message(t('The configuration options have been saved.'));
}
