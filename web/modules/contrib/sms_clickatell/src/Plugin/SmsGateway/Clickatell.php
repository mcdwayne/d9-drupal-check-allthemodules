<?php

namespace Drupal\sms_clickatell\Plugin\SmsGateway;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Entity\SmsMessageInterface as SmsMessageEntityInterface;
use Clickatell\Rest as ClickatellRest;
use Clickatell\ClickatellException;

/**
 * @SmsGateway(
 *   id = "clickatell",
 *   label = @Translation("Clickatell"),
 *   outgoing_message_max_recipients = 600,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 *   schedule_aware = TRUE,
 * )
 */
class Clickatell extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [];
    $defaults['account'] = [
      // REST.
      'auth_token' => '',
    ];
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['clickatell'] = [
      '#type' => 'details',
      '#title' => $this->t('Clickatell'),
      '#open' => TRUE,
    ];

    $form['clickatell']['auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authorization token'),
      '#default_value' => $config['account']['auth_token'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['account']['auth_token'] = trim($form_state->getValue('auth_token'));
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    $result = new SmsMessageResult();

    $api = new ClickatellRest(
      $this->configuration['account']['auth_token']
    );

    $message = [];
    if ($sms_message instanceof SmsMessageEntityInterface) {
      // See: https://www.clickatell.com/developers/api-docs/scheduled-delivery-advanced-message-send/
      if ($time = $sms_message->getSendTime()) {
        // Don't schedule time if now or soon (in case of time sync issues).
        // This is because the Clickatell API cannot handle times in the past,
        // throws invalid argument error 101.
        // Soon = scheduled_time + 30 mins.
        $date = DrupalDateTime::createFromTimestamp($time);
        $limit = (new DrupalDateTime())
          ->add(new \DateInterval('PT30M'));
        if ($date > $limit) {
          $message['scheduledDeliveryTime'] = $date->format('Y-m-d\TH:i:s\Z');
        }
      }
    }

    $recipients = $sms_message->getRecipients();
    $message['to'] = $recipients;
    $message['content'] = $sms_message->getMessage();
    $response = $api->sendMessage($message);

    // Unfortunately the Clickatell library (arcturial/clickatell) does not have
    // very good error handling. An empty response will be given if the request
    // fails.
    // See https://github.com/arcturial/clickatell/issues/25
    if (empty($response)) {
      return $result
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage('The request failed for some reason.');
    }

    // Response documentation.
    // https://www.clickatell.com/developers/api-documentation/rest-api-send-message/

    $reports = [];
    foreach ($response as $message_result) {
      $report = new SmsDeliveryReport();

      /** @var string|null $message_id */
      $message_id = $message_result['apiMessageId'];
      /** @var boolean $accepted */
      $accepted = $message_result['accepted'];
      /** @var string $recipient */
      $recipient = $message_result['to'];
      /** @var string|null $error_message */
      $error_message = $message_result['error'];

      $report->setRecipient($recipient);
      if ($message_id) {
        $report->setMessageId($message_id);
      }

      // If $error_code is FALSE or NULL then there was an no error.
      if (NULL !== $accepted) {
        // Success!
        $report->setStatus(SmsMessageReportStatus::QUEUED);
      }
      else {
        // We don't have error codes yet in this API.
        $report
          ->setStatus(SmsMessageReportStatus::ERROR)
          ->setStatusMessage(sprintf('Error: %s', $error_message));
      }

      $result->addReport($report);
    }

    return $result;
  }

}
