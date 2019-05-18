<?php

namespace Drupal\helpfulness\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for the administration values.
 */
class HelpfulnessAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helpfulness_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['helpfulness.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration.
    $config = $this->config('helpfulness.settings');

    // Options if the user selected "Yes".
    $form['yes_options'] = [
      '#type' => 'details',
      '#title' => t('"YES" Options'),
    ];

    $form['yes_options']['helpfulness_yes_title'] = [
      '#type' => 'text_format',
      '#title' => t('Title for the comments'),
      '#default_value' => $config->get('helpfulness_yes_title'),
      '#description' => t('Markup for the title if the user selects "yes". This text will appear above the comments area.'),
    ];

    $form['yes_options']['helpfulness_yes_description'] = [
      '#type' => 'text_format',
      '#title' => t('Description for the comments'),
      '#default_value' => $config->get('helpfulness_yes_description'),
      '#description' => t('Markup for the description if the user selects "yes". This text will appear below the comments area'),
    ];

    // Options if the user selected "No".
    $form['no_options'] = [
      '#type' => 'details',
      '#title' => t('"NO" Options'),
    ];

    $form['no_options']['helpfulness_no_title'] = [
      '#type' => 'text_format',
      '#title' => t('Title for the comments'),
      '#default_value' => $config->get('helpfulness_no_title'),
      '#description' => t('Markup for the title if the user selects "no". This text will appear above the comments area.'),
    ];

    $form['no_options']['helpfulness_no_description'] = [
      '#type' => 'text_format',
      '#title' => t('Description for the comments'),
      '#default_value' => $config->get('helpfulness_no_description'),
      '#description' => t('Markup for the description if the user selects "no". This text will appear below the comments area'),
    ];

    // Options for the comments.
    $form['comment_options'] = [
      '#type' => 'details',
      '#title' => t('Comment options'),
    ];

    $form['comment_options']['helpfulness_comment_required'] = [
      '#type' => 'radios',
      '#title' => t('Are comments required to submit feedback?'),
      '#default_value' => $config->get('helpfulness_comment_required', 0),
      '#options' => [0 => t('No'), 1 => t('Yes')],
    ];

    $form['comment_options']['helpfulness_comment_required_message'] = [
      '#type' => 'textarea',
      '#title' => t('Message Text'),
      '#default_value' => $config->get('helpfulness_comment_required_message', ''),
      '#description' => t('Text for error message if feedback was submitted without a comment.'),
    ];

    // Options for notification emails.
    $form['email_options'] = [
      '#type' => 'details',
      '#title' => t('Email options'),
    ];

    $form['email_options']['helpfulness_notification_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#default_value' => $config->get('helpfulness_notification_email'),
      '#description' => t('Enter an email to receive notifications if new feedback has been submitted.<br>If no email address is given then no notification email will be send.'),
    ];

    $form['email_options']['helpfulness_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $config->get('helpfulness_notification_subject'),
      '#description' => t('Subject for the notification email.'),
    ];

    $form['email_options']['helpfulness_notification_message_prefix'] = [
      '#type' => 'textarea',
      '#title' => t('Prefix for message body'),
      '#default_value' => $config->get('helpfulness_notification_message_prefix'),
      '#description' => t('Text for the body of the notification email, will be prepended to values submitted by the user.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $notification_email = trim($form_state->getValue(['helpfulness_notification_email']));
    if (!empty($notification_email) && !valid_email_address($notification_email)) {
      $form_state->setErrorByName('helpfulness_notification_email', t('The email address you entered is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('helpfulness.settings');

    $config->set('helpfulness_yes_title', $form_state->getValue('helpfulness_yes_title')['value'])
      ->set('helpfulness_yes_description', $form_state->getValue('helpfulness_yes_description')['value'])
      ->set('helpfulness_no_title', $form_state->getValue('helpfulness_no_title')['value'])
      ->set('helpfulness_no_description', $form_state->getValue('helpfulness_no_description')['value'])
      ->set('helpfulness_comment_required', $form_state->getValue('helpfulness_comment_required')['value'])
      ->set('helpfulness_comment_required_message', $form_state->getValue('helpfulness_comment_required_message'))
      ->set('helpfulness_notification_email', $form_state->getValue('helpfulness_notification_email'))
      ->set('helpfulness_notification_subject', $form_state->getValue('helfulness_notification_subject'))
      ->set('helpfulness_notification_message_prefix', $form_state->getValue('helpfulness_notification_message_prefix'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
