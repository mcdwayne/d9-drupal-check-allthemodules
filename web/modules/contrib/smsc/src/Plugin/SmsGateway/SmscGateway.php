<?php

/**
 * @file
 */

namespace Drupal\smsc\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\smsc\Smsc\DrupalSmsc;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SmsGateway(
 *   id = "smsc_gateway",
 *   label = @Translation("SMSC Gateway"),
 *   outgoing_message_max_recipients = -1,
 * )
 */
class SmscGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return \Drupal::config('smsc.config')->getOriginal();
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array                                                     $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string                                                    $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed                                                     $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id = 'smsc', $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $settingsLink = Url::fromRoute('smsc.smsc_settings')->toString();

    $form['smsc'] = [
      '#type'  => 'details',
      '#title' => $this->t('Twilio'),
      '#open'  => TRUE,
    ];

    $form['smsc']['markup'] = [
      '#markup' => $this->t('<a href=":url">SMSC-account</a> settings', [':url' => $settingsLink]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $smsMessage) {
    $result = new SmsMessageResult();

    $recipient = implode(',', $smsMessage->getRecipients());
    $message   = $smsMessage->getMessage();

    $report = new SmsDeliveryReport();

    $response = DrupalSmsc::sendSms($recipient, $message);

    $report->setRecipient($recipient);

    $report->setStatus(SmsMessageReportStatus::QUEUED);
    $report->setMessageId(time());

    if ($report->getStatus()) {
      $result->addReport($report);
    }

    return $result;
  }
}
