<?php

/**
 * @file
 * Contains \Drupal\email_auto_login_test\Form\SettingsFormTest.
 */

namespace Drupal\email_auto_login_test\Form;

use Drupal\Core\Form\FormBase;

/**
 * Configures aggregator settings for this site.
 */
class SettingsFormTest extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_auto_login_settings_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $account = \Drupal::currentUser();

    $form['account'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#required' => TRUE,
      '#autocomplete_route_name' => 'user.autocomplete',
      '#default_value' => $account->getUsername(),
    );

    $form['body'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#required' => TRUE,
      '#default_value' => $this->t("Hi, you are able to login by !link link!", array(
        '!link' => l('this', '<front>', array('absolute' => TRUE))
      )),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Send')
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $account = user_load_by_name($form_state['values']['account']);

    if ($account) {
      $form_state['values']['account'] = user_load_by_name($form_state['values']['account']);
    }
    else {
      form_set_error('account', $this->t('User is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $account = $form_state['values']['account'];

    $params['account'] = $account;
    $params['body'] = $form_state['values']['body'];

    drupal_mail('email_auto_login_test', 'test', $account->mail, $account->language, $params);
  }
}
