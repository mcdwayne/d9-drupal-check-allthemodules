<?php

namespace Drupal\marketing_cloud_example\Plugin\WebformHandler;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "marketing_cloud_example",
 *   label = @Translation("Marketing Cloud Example"),
 *   category = @Translation("Form Handler"),
 *   description = @Translation("Send an SMS on form submission"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class MarketingCloudExample extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'short_code' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['short_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short Code'),
      '#description' => $this->t('Short code  - this is the base message code in SF that we will override.'),
      '#default_value' => $this->configuration['short_code'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->configuration['short_code'] = $form_state->getValue('short_code');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $values = $webform_submission->getData();
    $mobileNumbers = !is_array($values['mobile_number']) ? [$values['mobile_number']] : $values['mobile_number'];
    $date = new \DateTime();
    // Arbitrary time in the past to send instantly.
    $date->modify('-1 day');
    $messageId = $this->configuration['short_code'];
    $data = [
      'mobileNumbers' => $mobileNumbers,
      'override' => TRUE,
      'messageText' => $values['message_text'],
      'sendTime' => $date->format('Y-m-d H:i'),
    ];
    $response = \Drupal::service('marketing_cloud_sms.service')
      ->postMessageToNumber($messageId, $data);
    if (!$response) {
      // The service returned FALSE, this is ba a gracefully handled error
      // (e.g. invalid JSON, invalid login details, etc), and details are
      // logged in Watchdog.
      $message = t('SMS send failed. Please check the log.');
      $this->messenger()->addError($message);
    }
    elseif (isset($response['errors'])) {
      // Salesforce has returned 200 Success with an error object, so we need
      // to handle this case. This is not automatically treated as an error by
      // the module, because it may be an expected response.
      $message = t('Salesforce returned an error - errors: %errors', ['%errors' => $response['errors']]);
      $this->messenger()->addError($message);
      $this->getLogger()->error($message);
    }
    elseif (!isset($response['result'])) {
      // An error was returned by Salesforce, so to maintain consistency with
      // the 200 with errors object, we validate against an expected response.
      $message = t('Salesforce returned an error - errors: %errors', ['%errors' => print_r($response, TRUE)]);
      $this->messenger()->addError($message);
      $this->getLogger()->error($message);
    }
    else {
      // Fall through to a successful response.
      $message = t('Success, response from SalesForce: "%result"', ['%result' => $response['result']]);
      $this->messenger()->addMessage($message);
      $this->getLogger()->info($message);
    }
  }

}
