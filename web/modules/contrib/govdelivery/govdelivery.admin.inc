<?php

/**
 * @file
 * GovDelivery TMS admin UI.
 */

/**
 * Signup an email address for a specified list.
 *
 * Pass any known questions and answers along.
 *
 * @return array
 *   Test subscriptions form.
 */
function govdelivery_test_subscriptions($form, &$form_state) {
  $form = array(
    'govdelivery_test_subscriptions' => array(
      '#type' => 'fieldset',
      '#title' => t('GovDelivery settings test - subscriptions'),
      '#tree' => TRUE,
      'subscriber' => array(
        '#type' => 'textfield',
        '#title' => t("Subscriber's e-mail"),
      ),
      'list_code' => array(
        '#type' => 'textfield',
        '#title' => t('Subscription list code'),
      ),
      // Use Queue option.
      'test' => array(
        '#type' => 'submit',
        '#value' => t('Send test message'),
      ),
    ),
  );
  return $form;
}

/**
 * Validate number of subscribers.
 *
 * Need validation that there is only one subscriber or change to handle
 * multiple subscriptions.
 */
function govdelivery_test_subscriptions_submit($form, $postinfo) {
  $subscriber = $postinfo['values']['govdelivery_test_subscriptions']['subscriber'];
  $list_code = $postinfo['values']['govdelivery_test_subscriptions']['list_code'];

  // Commented code:
  // if (isset($subscriber) && !empty($subscriber) &&
  // isset($list_code) && !empty($list_code)) {
  $status = govdelivery_subscribe($list_code, $subscriber, FALSE);
  // }
  drupal_set_message(t('%response', array('%response' => $status ? 'Subscription added.' : 'Subscription failed.')));
}
