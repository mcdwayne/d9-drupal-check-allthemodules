<?php

namespace Drupal\sms_rangine\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResultStatus;


/**
 * @SmsGateway(
 *   id = "rangine",
 *   label = "Rangine",
 *   outgoing_message_max_recipients = 1,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 * )
 */
class RangineGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Map Messente status codes to sms framework compatible ones
   *
   * @var array
   */
  protected static $statusMap = [
    'DELIVRD' => SmsMessageReportStatus::DELIVERED,
    'UNDELIV' => SmsMessageReportStatus::REJECTED,
    'FAILED' => SmsMessageReportStatus::REJECTED,
    'UNKNOWN' => SmsMessageReportStatus::ERROR,
    'ACCEPTD' => SmsMessageReportStatus::QUEUED,
    'REJECTD' => SmsMessageReportStatus::REJECTED,
    'DELETED' => SmsMessageReportStatus::EXPIRED,
    'EXPIRED' => SmsMessageReportStatus::EXPIRED,
    'NACK' => SmsMessageReportStatus::REJECTED,
  ];

  /**
   * Constructs a new Rangine instance.
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
      'user' => '',
      'pass' => '',
      'sender' => '',
      'confirm' => 'A SMS has sent from site to {number} and will achieve to phone in 3 minute if ther is no problem in communication or the phone is not off.',
      'host' => '37.130.202.188',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['rangine'] = [
      '#type' => 'details',
      '#title' => 'Rangine',
      '#open' => TRUE,
    ];
	
    $form['rangine']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Name'),
      '#default_value' => $config['user'],
      '#required' => TRUE,
      '#description' => $this->t('Your user name in Rangine SMS Service.'),

    ];
    $form['rangine']['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config['pass'],
      '#required' => TRUE,
      '#description' => $this->t('Your current password in Rangine SMS service. <b>Note:</b> Change it when you change your password in rangine SMS service'),
    ];
    $form['rangine']['sender'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender'),
      '#default_value' => $config['sender'],
      '#required' => TRUE,
      '#description' => $this->t('Input one of your active SMS line number.'),
    ];
    $form['rangine']['confirm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirm Message'),
      '#default_value' => $config['confirm'],
      '#required' => TRUE,
      '#description' => $this->t('This message will be shown after successful SMS sending. Use "{number}" for insert number in the message.'),
    ];
    $form['rangine']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rangine Api IP'),
      '#default_value' => $config['host'],
      '#required' => TRUE,
	  '#description' => $this->t('If checked send nothing but show the message that will be send if this was unchecked!.'),
    ];
    $form['rangine']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Mode'),
      '#default_value' => $config['debug'],
	  '#description' => $this->t('If checked send nothing but show the message that will be send if this was unchecked!.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['user'] = trim($form_state->getValue('user'));
    $this->configuration['pass'] = trim($form_state->getValue('pass'));
    $this->configuration['sender'] = trim($form_state->getValue('sender'));
    $this->configuration['confirm'] = trim($form_state->getValue('confirm'));
    $this->configuration['host'] = trim($form_state->getValue('host'));
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {

    $result = new SmsMessageResult();
    $report = new SmsDeliveryReport();

    $param = array(
      'uname' => $this->configuration['user'],
      'pass' => $this->configuration['pass'],
      'from' => $this->configuration['sender'],
      'message' => $sms_message->getMessage(),
      'to' => $sms_message->getRecipients()[0],
      'op' => 'send',
    );
	$debug= $this->configuration['debug'];

    try {
     // $response = $this->httpClient->request('post', 'http://'.$this->configuration['host'].'/services.jspd', $postArray);
		$url = $this->configuration['host'].'/services.jspd';

		$handler = curl_init($url);             
		curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($handler, CURLOPT_POSTFIELDS, $param);                       
		curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($handler);
	  $responseArray = json_decode($response);
	  $res_code = $responseArray[0];
	  $res_data = $responseArray[1];
    } catch (RequestException $e) {
      $report->setStatus(SmsMessageReportStatus::ERROR);
      $report->setStatusMessage($e->getMessage());
      return $result
        ->addReport($report)
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage('The request failed for some reason.');
    }

    $status = $res_code;
      // Check if the sms delivery request was successful
      if($res_code == 0){
        $report->setStatus(SmsMessageReportStatus::QUEUED);
        $report->setMessageId($res_code);
      } else {
        $report->setStatus(SmsMessageReportStatus::ERROR);
        $report->setStatusMessage('Sending message failed with error code: '.$res_code);
      }


    $report->setRecipient($sms_message->getRecipients()[0]);

    $result->addReport($report);

    return $result;
  }

}