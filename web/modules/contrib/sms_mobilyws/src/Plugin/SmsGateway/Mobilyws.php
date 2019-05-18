<?php

namespace Drupal\sms_mobilyws\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @SmsGateway(
 *   id = "mobilyws",
 *   label = @Translation("Mobilyws"),
 *   outgoing_message_max_recipients = 5000,
 *   credit_balance_available = TRUE
 * )
 */
class Mobilyws extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs an instance of the plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mobilyws_sender_id' => '',
      'mobilyws_user'      => '',
      'mobilyws_password'  => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['mobilyws'] = [
      '#type'  => 'details',
      '#title' => $this->t('Mobiley.ws'),
      '#open'  => TRUE,
    ];

    $form['mobilyws']['help'] = [
      '#type'  => 'html_tag',
      '#tag'   => 'p',
      '#value' => $this->t('To get your Sender ID, User, and Password information, Create an account here: <a href="https://www.mobily.ws/sms/index.php">https://www.mobily.ws/sms/index.php</a>.'),
    ];

    $form['mobilyws']['mobilyws_sender_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Sender ID'),
      '#default_value' => $config['mobilyws_sender_id'],
      '#description'   => t('The sender name of your Mobily.ws account.'),
      '#placeholder'   => 'XXXXXXXXXXXX',
      '#required'      => TRUE,
    ];

    $form['mobilyws']['mobilyws_user'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('User'),
      '#default_value' => $config['mobilyws_user'],
      '#description'   => t('The username of your Mobily.ws account.'),
      '#required'      => TRUE,
    ];

    $form['mobilyws']['mobilyws_password'] = [
      '#type'          => 'password',
      '#title'         => $this->t('Password'),
      '#default_value' => '',
      '#description'   => t('The password of your Mobily.ws account.'),
      '#required'      => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    $this->configuration['mobilyws_sender_id'] = trim($form_state->getValue('mobilyws_sender_id'));
    $this->configuration['mobilyws_user']      = trim($form_state->getValue('mobilyws_user'));
    $this->configuration['mobilyws_password']  = $form_state->getValue('mobilyws_password');
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    // Send SMS API, all parameters:
    // http://www.mobily.ws/api/msgSend.php?mobile=966555555555&password=123456&numbers=966555555555&sender=NEW%20SMS&msg=06270647064406270020064806330647064406270020062806430020064506390020006D006F00620069006C0079002E00770073&timeSend=0&dateSend=0&deleteKey=45871&msgId=15174&applicationType=68&domainName=localhost&notRepeat=1
    // Send SMS API with lang=3, default parameters:
    // http://www.mobily.ws/api/msgSend.php?mobile=966555555555&password=123456&numbers=966555555555&sender=NEW%20SMS&msg=Hello20%World&applicationType=68&lang=3

    $result = new SmsMessageResult();
    $report = new SmsDeliveryReport();

    $uri = 'http://mobily.ws/api/msgSend.php?';

    $options['form_params'] = [
      'mobile'           => $this->configuration['mobilyws_user'],
      'password'         => $this->configuration['mobilyws_password'],
      'numbers'          => $sms_message->getRecipients()[0],
      'sender'           => $this->configuration['mobilyws_sender_id'],
      'msg'              => $sms_message->getMessage(),
      'lang'             => '3',
      'application_type' => '68'
      //'domainName'       => \Drupal::request()->getHost()
    ];

    try {
      $response = $this->httpClient->request('post', $uri, $options);
    }
    catch (RequestException $e) {
      $report->setStatus(SmsMessageReportStatus::ERROR);
      $report->setStatusMessage($e->getMessage());
      return $result
        ->addReport($report)
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage('The request failed for some reason.');
    }

    $status = $response->getStatusCode();
    if ($status == 200) {
      // Returned successful response, parsing it
      $resp = $response->getBody()->__toString();

      // Check if the sms delivery request was successful
      if ($resp == '1') {
        $report->setStatus(SmsMessageReportStatus::QUEUED);
      }
      else {
        $report->setStatus(SmsMessageReportStatus::ERROR);
        $report->setStatusMessage('Sending message failed with error code: ' . $resp);
      }
    }

    $report->setRecipient($sms_message->getRecipients()[0]);

    $result->addReport($report);

    return $result;
  }
}
