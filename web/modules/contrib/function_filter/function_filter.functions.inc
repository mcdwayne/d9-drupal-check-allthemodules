<?php

/**
 * Return current date.
 */
function function_filter__current_date($format = 'r') {
  return date($format);
}

/**
 * Return contact form.
 */
function function_filter__contact_form($contact_form_id) {
  $contact_message = \Drupal::entityTypeManager()
    ->getStorage('contact_message')
    ->create(['contact_form' => $contact_form_id]);

  $form = \Drupal::service('entity.form_builder')->getForm($contact_message);
  $form['#cache']['contexts'][] = 'user.permissions';

  return $form;
}
