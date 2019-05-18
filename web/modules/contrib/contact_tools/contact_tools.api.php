<?php

/**
 * @file
 * Hook examples defined by this module.
 */

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows you to alter link and url options for modal links.
 *
 * @param array $link_options
 *   An array of options for modal window.
 * @param array $context
 *   An array with additional information contains: contact form name, modal
 *   link type and link title.
 */
function hook_contact_tools_modal_link_options_alter(array &$link_options, array $context) {
  $link_options['width'] = 600;
  $link_options['dialogClass'] = 'my-special-form';
}

/**
 * Allows to alter AJAX response handled by the module.
 *
 * You can fully alter, remove and add new commands to response.
 *
 * @param \Drupal\core\Ajax\AjaxResponse $ajax_response
 *   Ajax Response object.
 * @param array $form
 *   Form's render array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 */
function hook_contact_tools_ajax_response_alter(\Drupal\core\Ajax\AjaxResponse &$ajax_response, array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
  if ($form_state->isExecuted()) {
    $ajax_response->addCommand(new ReplaceCommand('#contact-form-' . $form['#build_id'], t('Thank you for your submission!')));
  }
}

/**
 * Allows modules to alter AJAX response handled by the module.
 *
 * You can fully alter, remove and add new commands to response.
 *
 * This hook only apply for specified contact form name. You must pass only
 * machine name of contact form. F.e. is form has form_id
 * "contact_message_feedback_form" so form name here is "feedback". In other
 * words, this is bundle name of the contact_message entity.
 *
 * @param \Drupal\core\Ajax\AjaxResponse $ajax_response
 *   Ajax Response object.
 * @param array $form
 *   Form's render array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 */
function hook_contact_tools_CONTACT_NAME_ajax_response_alter(\Drupal\core\Ajax\AjaxResponse &$ajax_response, array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
  if ($form_state->isExecuted()) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $ajax_response->addCommand(new RedirectCommand($base_url . '/submission-complete'));
  }
}

/**
 * @} End of "addtogroup hooks".
 */
