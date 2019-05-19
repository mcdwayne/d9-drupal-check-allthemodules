<?php

namespace Drupal\sms_gateway_base\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SmsGateway(
 *   id = "debug",
 *   label = @Translation("Debug Gateway"),
 * )
 */
class DebugGateway extends SmsGatewayPluginBase {

  /**
   * The configuration settings for debug gateway.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'random' => '',
      'message_failure' => '',
      'delivery_failure' => '',
      'retain_dlrs' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function balance() {
    return 10000;
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms) {
    // Log the messages and generate message ID's for each sender.
    $reports = [];
    $msg_ids = '';
    foreach ($sms->getRecipients() as $number) {
      // Coin toss to determine if message 'fails' or 'succeeds'.
      if (rand(0, 100) < $this->configuration['message_failure']) {
        // Falls into the 'failed' percentage.
        // Generate random error message.
        $err = rand(-13, -1);
        $msg_ids .= $err . "\n";
        $reports[$number] = [
          'status' => FALSE,
          'message_id' => '',
          'error_code' => $err,
          'error_message' => self::$errorCodes[$err],
        ];
      }
      else {
        // Falls into the 'succeeded' percentage.
        $msg_id = $this->randomID();
        $msg_ids .= $msg_id . "\n";
        $reports[$number] = array(
          'status' => TRUE,
          'message_id' => $msg_id,
          'error_code' => 0,
          'error_message' => '',
        );
      }
    }
    $numbers = implode(',', $sms->getRecipients());
    $this->logger()->notice("SMS message sent to %number with the text: @message\nmessage ids: @msgids",
      array('%number' => $numbers, '@message' => $sms->getMessage(), '@msgids' => $msg_ids));

    // Save the message IDs for delivery report generation.
    // @todo Optimize this later
    $delivery_report = $this->config()->get('delivery_report');
    $delivery_report[] = $reports;
    $this->config()->set('delivery_report', $delivery_report)->save();

    return new SmsMessageResult($reports);
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $reports = array(
      'status' => TRUE,
      'data' => $this->config()->get('delivery_report'),
    );
    if (!$this->getCustomConfiguration()['retain_dlrs']) {
      $this->config()->delete('delivery_report')->save();
    }
    return $reports;
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['balance'] = array(
      '#type' => 'item',
      '#title' => $this->t('Current balance'),
      '#markup' => $this->balance(),
    );
    
    $config = $this->getCustomConfiguration() + $this->defaultConfiguration();

    $form['debug'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Random failure settings'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );
    $form['debug']['random'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable random failures'),
      '#description' => $this->t('Enable generation of random errors to simulate real world messaging.'),
      '#default_value' => $config['random'],
    );
    $form['debug']['message_failure'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Message failure rate'),
      '#description' => $this->t('Simulated rate for failure of messages (percentage).'),
      '#field_suffix' => '%',
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $config['message_failure'],
    );
    $form['debug']['delivery_failure'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Message delivery failure rate'),
      '#description' => $this->t('Simulated rate for failure of delivery reports (percentage).'),
      '#field_suffix' => '%',
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $config['delivery_failure'],
    );

    $form['retain_dlrs'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Retain delivery reports after poll'),
      '#description' => $this->t('Retains delivery reports after they have been polled. <br/>Default behaviour is to delete'),
      '#default_value' => $config['retain_dlrs'],
    );

    $form['clear_dlrs'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Clear all delivery reports now'),
      '#description' => $this->t('Clear all delivery reports and records stored'),
    );

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Update the configuration.
    $custom = $form_state->getValue('debug') + $this->getCustomConfiguration();
    $custom['retain_dlrs'] = $form_state->getValue('retain_dlrs');

    // @todo: Storage for this should be moved to a more appropriate location.
    if ($form_state->getValue('clear_dlrs')) {
      $custom['debug_reports'] = array();
    }

    $this->setCustomConfiguration($custom);
  }

  /**
   * Generates random message id's for gateways that don't autogenerate
   */
  protected function randomID() {
    if (!isset($this->sessid)) {
      $this->sessid = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
    }
    return time() . $this->sessid . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
  }

  /**
   * Returns the configuration object containing settings.
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function config() {
    if (!isset($this->config)) {
      $this->config = \Drupal::config('sms.debug_gateway.reports');
    }
    return $this->config;
  }

  /**
   * Debug error codes
   */
  static $errorCodes = array(
    '-1' => 'SEND_ERROR',
    '-2' => 'NOT_ENOUGHCREDITS',
    '-3' => 'NETWORK_NOTCOVERED',
    '-4' => 'SOCKET_EXCEPTION',
    '-5' => 'INVALID_USER_OR_PASS',
    '-6' => 'MISSING_DESTINATION_ADDRESS',
    '-7' => 'MISSING_SMSTEXT',
    '-8' => 'MISSING_SENDERNAME',
    '-9' => 'DESTADDR_INVALIDFORMAT',
    '-10' => 'MISSING_USERNAME',
    '-11' => 'MISSING_PASS',
    '-12' => 'MISSING_WAPURL',
    '-13' => 'INVALID_DESTINATION_ADDRESS',
  );

}
