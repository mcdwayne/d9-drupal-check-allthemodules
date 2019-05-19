<?php

namespace Drupal\webform_send_multiple_emails\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;

/**
 * Email webform submission to multiple recipients, sending to each individually.
 *
 * @WebformHandler(
 *   id = "email_send_multiple",
 *   label = @Translation("Send Multiple Emails"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via an email to each recipient individually."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class EmailSendMultipleWebformHandler extends EmailWebformHandler {

  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['prefix_text'] = '';
    $config['prefix_multiple_field'] = '';
    $config['mail_to_default_enabled'] = FALSE;
    $config['mail_to_default_address'] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable cc, bcc fields to prevent sending same email multiple times to these.
    // TODO: find alternative to allow these fields to be used.
    $form['to']['cc_mail']['cc_mail']['#attributes']['disabled'] = 'disabled';
    $form['to']['bcc_mail']['bcc_mail']['#attributes']['disabled'] = 'disabled';

    $form['message_prefix'] = [
      '#type' => 'details',
      '#title' => 'Message prefix',
      'prefix_text' => [
        '#title' => 'Prefix text',
        '#type' => 'textfield',
        '#description' => t('Adds text to the beginning of the body message. If adding multiple names then add "[prefix_multiple_field]". E.g. Dear [prefix_multiple_field],'),
        '#default_value' => $this->configuration['prefix_text'],
      ],
      'prefix_multiple_field' => [
        '#title' => 'Prefix multiple field',
        '#type' => 'textfield',
        '#description' => t('Field containing list of corresponding salutation names with multiple emails. E.g. "[webform_submission:values:postcode_search:ep_names_list]"'),
        '#default_value' => $this->configuration['prefix_multiple_field'],
      ],
    ];

    $form['mail_to_default'] = [
      '#type' => 'details',
      '#title' => t('Send email to default'),
      'mail_to_default_enabled' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Send email from this handler to default recipient'),
        '#default_value' => $this->configuration['mail_to_default_enabled'],
        '#return_value' => TRUE,
        '#description' => $this->t('Send all email from this handler to the default email address specified. Useful when testing the webform that the emails are correct.')
      ],
      'mail_to_default_address' => [
        '#title' => 'Default email address',
        '#type' => 'email',
        '#default_value' => $this->configuration['mail_to_default_address'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if ($this->configuration['states'] && in_array($state, $this->configuration['states'])) {
      $message = $this->getMessage($webform_submission);
      $to_mail_list = explode(',',$message['to_mail']);

      $prefix_text = '';
      if (!empty($this->configuration['prefix_text'])) {
        $prefix_text = $this->configuration['prefix_text'];
      }

      if (!empty($this->configuration['prefix_multiple_field'])) {
        // Get original "to_mail" value before it is processed by mail handler.
        // Build lookup of email addresses and corresponding names.
        $to_mail_list_original = explode(',', $this->tokenManager->replace($this->configuration['to_mail'], $webform_submission));
        $to_names_list = explode(':', $this->tokenManager->replace($this->configuration['prefix_multiple_field'], $webform_submission));
        $to_names_lookup = [];
        foreach ($to_mail_list_original as $index => $mail) {
          $to_names_lookup[$mail] = $to_names_list[$index];
        }
      }

      $message_body_original = $message['body'];
      foreach ($to_mail_list as $to_mail) {
        $message['to_mail'] = $to_mail;
        if (!empty($to_names_lookup)) {
          // Add prefix to message body with a name matching the email address.
          $prefix_text_replaced = str_replace('[prefix_multiple_field]', $to_names_lookup[$to_mail], $prefix_text);
          if ($this->configuration['html']) {
            $prefix_text_replaced = '<p>' . $prefix_text_replaced . '</p>';
          }
          $message['body'] = $prefix_text_replaced . $message['body'];

          if ($this->configuration['mail_to_default_enabled'] === TRUE) {
            $message['to_mail'] = $this->configuration['mail_to_default_address'];
          }
          else {
            $message['to_mail'] = $to_mail;
          }
        }
        $this->sendMessage($webform_submission, $message);
        $message['body'] = $message_body_original;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    if (in_array(WebformSubmissionInterface::STATE_DELETED, $this->configuration['states'])) {
      $message = $this->getMessage($webform_submission);
      $to_mail_list = explode(',',$message['to_mail']);
      foreach ($to_mail_list as $to_mail) {
        if ($this->configuration['mail_to_default_enabled'] === TRUE) {
          $message['to_mail'] = $this->configuration['mail_to_default_address'];
        }
        else {
          $message['to_mail'] = $to_mail;
        }
        $this->sendMessage($webform_submission, $message);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['prefix_text'] = $values['message_prefix']['prefix_text'];
    $this->configuration['prefix_multiple_field'] = $values['message_prefix']['prefix_multiple_field'];
    $this->configuration['mail_to_default_enabled'] = $values['mail_to_default']['mail_to_default_enabled'];
    $this->configuration['mail_to_default_address'] = $values['mail_to_default']['mail_to_default_address'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['mail_to_default']['mail_to_default_enabled'] === TRUE && empty($values['mail_to_default']['mail_to_default_address'])) {
      $form_state->setErrorByName('mail_to_default][mail_to_default_address', $this->t('The Default email address cannot be empty'));
    }
    parent::validateConfigurationForm($form, $form_state);

  }

}
