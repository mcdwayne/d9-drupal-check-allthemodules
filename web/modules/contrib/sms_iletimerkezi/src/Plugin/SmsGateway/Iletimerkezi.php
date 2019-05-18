<?php

namespace Drupal\sms_iletimerkezi\Plugin\SmsGateway;

use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\Core\Form\FormStateInterface;
use Emarka\Sms\Client;

/**
 * @file
 * Enables modules to use Iletimerkezi API, and integrates with SMS Framework.
 *
 * @SmsGateway(
 *   id = "iletimerkezi",
 *   label = @Translation("Iletimerkezi"),
 *   outgoing_message_max_recipients = 600,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 *   schedule_aware = TRUE,
 * )
 */
class Iletimerkezi extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [];
    $defaults['account'] = [
      'public_key'  => '',
      'private_key' => '',
    ];
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['iletimerkezi'] = [
      '#type'  => 'details',
      '#title' => $this->t('Iletimerkezi'),
      '#open'  => TRUE,
    ];

    $form['iletimerkezi']['public_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Public Key'),
      '#default_value' => $config['account']['public_key'],
    ];

    $form['iletimerkezi']['private_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Private Key'),
      '#default_value' => $config['account']['private_key'],
    ];

    $form['iletimerkezi']['sender'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Sender'),
      '#default_value' => $config['account']['sender'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['account']['public_key']  = trim($form_state->getValue('public_key'));
    $this->configuration['account']['private_key'] = trim($form_state->getValue('private_key'));
    $this->configuration['account']['sender']      = trim($form_state->getValue('sender'));
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {

    $client = Client::createClient([
      'api_key'        => $this->configuration['account']['public_key'],
      'secret'         => $this->configuration['account']['private_key'],
      'sender'         => $this->configuration['account']['sender'],
    ]);

    $result = new SmsMessageResult();

    try {

      $response = $client->send($sms_message->getRecipients(), $sms_message->getMessage());

      if (!$response) {
        return $result
          ->setError(SmsMessageResultStatus::ERROR)
          ->setErrorMessage('The request failed for some reason.');
      }

    }
    catch (Exception $e) {
      return $result
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage($e->getMessage());
    }

    foreach ($sms_message->getRecipients() as $receipt) {

      $report        = new SmsDeliveryReport();
      $message_id    = $response;
      $accepted      = TRUE;
      $recipient     = $receipt;
      $error_message = '';

      $report->setRecipient($receipt);
      $report->setMessageId($message_id);
      $report->setStatus(SmsMessageReportStatus::QUEUED);
      $result->addReport($report);
    }

    return $result;

  }

}
