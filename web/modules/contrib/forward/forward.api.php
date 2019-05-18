<?php

/**
 * @file
 * Hooks provided by the Forward module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Add tokens before replacements are made within a Forward email.
 *
 * @param $form_state
 *   A form_state being processed.  This parameter may be null.
 *
 * @return a token array
 *
 * A module implementing this hook must also have token processing
 * defined in its my_module.tokens.inc file, otherwise the tokens added
 * in this hook will never be replaced.
 *
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Utility%21token.api.php/8
 */
function hook_forward_token(FormStateInterface $form_state) {
  return ['my_module' => ('my_token' => 'my_value')];
}

/**
 * Alter the message body before it is rendered.
 *
 * @param $render_array
 *   The render array to alter.
 *
 * @param $form_state
 *   A form_state being processed.  Alterable.
 */
function hook_forward_mail_pre_render_alter(&$render_array, FormStateInterface &$form_state) {
  $render_array['#my_module'] = ['#markup' => 'my_data'];
}

/**
 * Alter the message body after it is rendered.
 *
 * @param $message_body
 *   The message content to alter.
 *
 * @param $form_state
 *   A form_state being processed.  Alterable.
 */
function hook_forward_mail_post_render_alter(&$message_body, FormStateInterface &$form_state) {
  $message_body .= '<div>This is some extra content.</div>';
}

/**
 * Post process the forward.
 *
 * @param $account
 *   The user account of the person who forwarded.
 * @param $entity
 *   The entity that was forwarded.
 * @param $form_state
 *   A form_state being processed.
 */
function hook_forward_entity(UserInterface $account, EntityInterface $entity, FormStateInterface $form_state) {
  // Example: redirect to the home page
  $form_state->setRedirect('<front>');
}

 /**
 * @} End of "addtogroup hooks".
 */
