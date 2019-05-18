<?php

/**
 * @file
 * Hooks provided by the address_usps module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Altering hook triggered on address_usps element.
 *
 * @param array $element
 *   Address USPS Render element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 * @param array $complete_form
 *   Complete entity form.
 *
 * @see \Drupal\address_usps\Element\AddressUSPS::processAddress
 */
function hook_address_usps_element_alter(array &$element, FormStateInterface &$form_state, array &$complete_form) {

}

/**
 * Altering hook triggered just in the end of Address USPS element validation.
 *
 * @param array $element
 *   Address USPS Render element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 * @param array $complete_form
 *   Complete entity form.
 *
 * @see \Drupal\address_usps\Element\AddressUSPS::uspsValidate
 */
function hook_address_usps_element_validation_alter(array &$element, FormStateInterface &$form_state, array &$complete_form) {

}

/**
 * Altering hook triggered just ajax response initialization.
 *
 * Contains next elements:
 * - $data['response'] AjaxResponse
 *   Ajax Response that contains some commands.
 * - $data['element'] array
 *   Address USPS Render element.
 * - $data['suggested_values']  array
 *   USPS service suggested address.
 * - $data['form_state'] Drupal\Core\Form\FormStateInterface
 *   Entity form state.
 * - $data['request'] Symfony\Component\HttpFoundation\Request
 *   Request object.
 *
 * @param array $data
 *   Data for altering. See details above.
 */
function hook_address_usps_element_ajax_response_pre_alter(array $data) {

}

/**
 * Altering hook triggered just after ajax response is build.
 *
 * Contains next elements:
 * - $data['response'] AjaxResponse
 *   Ajax Response that contains some commands.
 * - $data['element'] array
 *   Address USPS Render element.
 * - $data['suggested_values'] array
 *   USPS service suggested address.
 * - $data['form_state'] Drupal\Core\Form\FormStateInterface
 *   Entity form state.
 * - $data['request'] Symfony\Component\HttpFoundation\Request
 *   Request object.
 *
 * @param array $data
 *   Data for altering. See details above.
 */
function hook_address_usps_element_ajax_response_alter(array $data) {

}
