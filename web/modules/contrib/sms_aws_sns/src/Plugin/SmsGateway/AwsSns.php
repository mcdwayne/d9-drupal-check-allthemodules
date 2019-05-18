<?php

namespace Drupal\sms_aws_sns\Plugin\SmsGateway;

use Aws\Sns\Exception\SnsException;
use Aws\Sns\SnsClient;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\Core\Form\FormStateInterface;

/**
 * @SmsGateway(
 *   id = "aws_sns",
 *   label = @Translation("Amazon AWS SNS"),
 *   outgoing_message_max_recipients = 600,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 *   schedule_aware = TRUE,
 * )
 */
class AwsSns extends SmsGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'credentials' => [
        'key' => '',
        'secret' => '',
      ],
      'region' => '',
      'version' => '2010-03-31',
      'sms_attributes' => [
        'sender_name' => '',
        'sms_type' => 'Promotional',
        'delivery_status_iam_role' => '',
      ],
    ];

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['sns'] = [
      '#type' => 'details',
      '#title' => $this->t('Amazon SNS SMS'),
      '#open' => TRUE,
    ];

    $form['sns']['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region'),
      '#default_value' => $config['region'],
      '#required' => TRUE,
    ];

    $form['sns']['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
      '#default_value' => $config['version'],
      '#required' => TRUE,
    ];

    $form['sns']['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('Credentials'),
      '#open' => TRUE,
    ];

    $form['sns']['credentials']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => $config['credentials']['key'],
      '#required' => TRUE,
    ];

    $form['sns']['credentials']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#default_value' => $config['credentials']['secret'],
      '#required' => TRUE,
    ];

    $form['sns']['sms_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Sms attributes'),
      '#open' => FALSE,
    ];

    $form['sns']['sms_attributes']['sender_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender name'),
      '#default_value' => $config['sms_attributes']['sender_name'],
      '#description' => $this->t('The sender name of the text message with a max of 11 characters. If left empty the default Aws configuration will be used.'),
      '#maxlength' => 11,
    ];

    $form['sns']['sms_attributes']['sms_type'] = [
      '#type' => 'select',
      '#title' => $this->t('DefaultSMSType'),
      '#options' => [
        'Promotional' => 'Promotional',
        'Transactional' => 'Transactional',
      ],
      '#default_value' => $config['sms_attributes']['sms_type'],
      '#description' => $this->t('The type of SMS message that you will send by default.'),
    ];

    $form['sns']['sms_attributes']['delivery_status_iam_role'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DeliveryStatusIAMRole'),
      '#default_value' => $config['sms_attributes']['delivery_status_iam_role'],
      '#description' => $this->t('The ARN of the IAM role that allows Amazon SNS to write logs about SMS deliveries in CloudWatch Logs.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['credentials']['key'] = trim($form_state->getValue('key'));
    $this->configuration['credentials']['secret'] = trim($form_state->getValue('secret'));
    $this->configuration['region'] = trim($form_state->getValue('region'));
    $this->configuration['version'] = trim($form_state->getValue('version'));
    $this->configuration['sms_attributes']['sender_name'] = $form_state->getValue('sender_name');
    $this->configuration['sms_attributes']['sms_type'] = $form_state->getValue('sms_type');
    $this->configuration['sms_attributes']['delivery_status_iam_role'] = $form_state->getValue('delivery_status_iam_role');
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    $sns_client = new SnsClient([
      'region'  => $this->configuration['region'],
      'version' => $this->configuration['version'],
      'credentials' => [
        'key' => $this->configuration['credentials']['key'],
        'secret' => $this->configuration['credentials']['secret'],
      ],
    ]);

    $result = new SmsMessageResult();
    $args = $this->getBaseArgs();

    foreach ($sms_message->getRecipients() as $recipient) {
      $report = new SmsDeliveryReport();
      try {
        $args['Message'] = $sms_message->getMessage();
        $args['PhoneNumber'] = $recipient;

        $report->setRecipient($recipient);

        /** @var \Aws\Result $sns_result */
        $sns_result = $sns_client->publish($args);
        $meta_data = $sns_result->get('@metadata');
        $report->setMessageId($sns_result->get('MessageId'))
          ->setStatusMessage($meta_data['statusCode']);
        if ($meta_data['statusCode'] === 200) {
          $report->setStatus(SmsMessageReportStatus::QUEUED);
        } else {
          $report->setStatus(SmsMessageReportStatus::REJECTED);
        }
      }
      catch (SnsException $e) {
        $report->setStatus(SmsMessageReportStatus::ERROR);
        $report->setStatusMessage($e->getAwsErrorMessage());
      }
      $result->addReport($report);
    }

    return $result;
  }

  /**
   * @return array
   */
  protected function getBaseArgs() {
    $arg = [];
    $configuration = $this->configuration;
    $sms_attributes = $configuration['sms_attributes'];
    if (!empty($sms_attributes['sender_name'])) {
      $arg['MessageAttributes']['AWS.SNS.SMS.SenderID'] = [
        'DataType' => 'String',
        'StringValue' => $sms_attributes['sender_name'],
      ];
    }
    if (!empty($sms_attributes['sms_type'])) {
      $arg['MessageAttributes']['AWS.SNS.SMS.SMSType'] = [
        'DataType' => 'String',
        'StringValue' => $sms_attributes['sms_type'],
      ];
    }
    if (!empty($sms_attributes['delivery_status_iam_role'])) {
      $arg['MessageAttributes']['AWS.SNS.SMS.DeliveryStatusIAMRole'] = [
        'DataType' => 'String',
        'StringValue' => $sms_attributes['delivery_status_iam_role'],
      ];
    }

    return $arg;
  }

}
