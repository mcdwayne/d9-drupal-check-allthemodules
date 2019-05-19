<?php

namespace Drupal\sms_vianett\Plugin\SmsGateway;

use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\Core\Form\FormStateInterface;
use Vianett\Client;
use Vianett\Message;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageResultStatus;

/**
 * Defines a gateway storing transmitted SMS in memory.
 *
 * @SmsGateway(
 *   id = "vianett",
 *   label = @Translation("Vianett"),
 * )
 */
class SmsGatewayVianett extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'username' => '',
      'password' => '',
      'debug' => '',
      'sender' => '',

    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => t('User'),
      '#description' => t('The username of your vianett account.'),
      '#default_value' => $config['username'],
    );

    $form['password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#description' => t('The current password on your vianett account.'),
      '#default_value' => $config['password'],
    );

    $form['debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Debug Mode'),
      '#description' => t('Prevent from being sent any sms. Just show API request'),
      '#default_value' => $config['debug'],
    );

    $form['sender'] = array(
      '#type' => 'textfield',
      '#title' => t('Default sender'),
      '#default_value' => $config['sender'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['username'] = $form_state->getValue('username');
    $this->configuration['password'] = $form_state->getValue('password');
    $this->configuration['debug'] = $form_state->getValue('debug');
    $this->configuration['sender'] = $form_state->getValue('sender');
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    $username = $this->configuration['username'];
    $password = $this->configuration['password'];
    $sender = $this->configuration['sender'];
    $recipient = $sms_message->getRecipients()[0];
    $result = new SmsMessageResult();
    $message_id = (isset($this->configuration['message_id'])) ? $this->configuration['message_id'] : rand(1000, 10000000);

    $report = new SmsDeliveryReport();
    $report->setRecipient($recipient);

    // Create the client.
    try {
      // Create he client.
      $client = new Client($username, $password);
      $sms = new Message($client);
      // Set report status.
      $report->setStatus(SmsMessageReportStatus::QUEUED);
      $report->setMessageId($message_id);

      // Prepare a list of recipients.
      $sms_senders = implode(';', $sms_message->getRecipients());
      // Prepare SMS message.
      $sms->prepare($sender, $sms_senders, $sms_message->getMessage(), $message_id);
      // Send or log to default log.
      $sms->send();
    }
    catch (\Exception $e) {
      $result->setError(SmsMessageResultStatus::ACCOUNT_ERROR);
      $report->setStatus(SmsMessageReportStatus::ERROR);
      $report->setStatusMessage($e->getMessage());
    }
    if ($report->getStatus()) {
      $result->addReport($report);
    }
    return $result;
  }

}
