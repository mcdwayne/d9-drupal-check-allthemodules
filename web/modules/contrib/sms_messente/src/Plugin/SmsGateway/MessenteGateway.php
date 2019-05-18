<?php

namespace Drupal\sms_messente\Plugin\SmsGateway;

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
 *   id = "messente",
 *   label = "Messente",
 *   outgoing_message_max_recipients = 1,
 *   reports_pull = TRUE,
 *   reports_push = TRUE,
 * )
 */
class MessenteGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new Messente instance.
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
      'host' => 'api2.messente.com',
      'secure' => FALSE,
      'user' => '',
      'pass' => '',
      'dlr' => TRUE,
      'screen' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['messente'] = [
      '#type' => 'details',
      '#title' => 'Messente',
      '#open' => TRUE,
    ];

    $form['messente']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => $config['host'],
      '#recuired' => TRUE,
    ];

    $form['messente']['secure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use secure requests'),
      '#description' => $this->t('Changes default behaviour from http to https.'),
      '#default_value' => $config['secure'],
    ];

    $form['messente']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Username'),
      '#default_value' => $config['user'],
      '#required' => TRUE,
    ];

    $form['messente']['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Password'),
      '#default_value' => $config['pass'],
      '#required' => TRUE,
    ];

    $form['messente']['dlr'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send delivery report URL for asyncronous delivery reporting with every message'),
      '#default_value' => $config['dlr'],
    ];

    $form['messente']['screen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send straight to screen messages (Flash messages)'),
      '#default_value' => $config['screen'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['host'] = trim($form_state->getValue('host'));
    $this->configuration['secure'] = (boolean)$form_state->getValue('secure');
    $this->configuration['user'] = trim($form_state->getValue('user'));
    $this->configuration['pass'] = trim($form_state->getValue('pass'));
    $this->configuration['dlr'] = (boolean)$form_state->getValue('dlr');
    $this->configuration['screen'] = (boolean)$form_state->getValue('screen');
  }

  /**
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    $result = new SmsMessageResult();
    $report = new SmsDeliveryReport();

    $postArray = [];
    $postArray['form_params'] = [
      'username' => $this->configuration['user'],
      'password' => $this->configuration['pass'],
      'text' => $sms_message->getMessage(),
      'to' => $sms_message->getRecipients()[0],
    ];
    if ($this->configuration['dlr']) {
      $postArray['dlr-url'] = ($this->configuration['secure']? 'https://':'http://').\Drupal::request()->getHost().$sms_message->getGateway()->getPushReportPath();
    }
    if ($this->configuration['screen']) {
      $postArray['mclass'] = 0;
    }

    try {
      $response = $this->httpClient->request('post', ($this->configuration['secure']? 'https://':'http://').$this->configuration['host'].'/send_sms/', $postArray);
    } catch (RequestException $e) {
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
      list($resp, $code) = explode(' ', $response->getBody()->__toString());

      // Check if the sms delivery request was successful
      if ($resp == 'OK') {
        $report->setStatus(SmsMessageReportStatus::QUEUED);
        $report->setMessageId($code);
      } else {
        $report->setStatus(SmsMessageReportStatus::ERROR);
        $report->setStatusMessage('Sending message failed with error code: '.$code);
      }
    }

    $report->setRecipient($sms_message->getRecipients()[0]);

    $result->addReport($report);

    return $result;
  }

  /**
   * Parses incoming delivery reports and returns the created delivery reports.
   *
   * The request contains delivery reports pushed to the site in a format
   * supplied by the gateway API. This method transforms the raw request into
   * delivery report objects usable by SMS Framework.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object containing the unprocessed delivery reports.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   HTTP response to return to the server pushing the raw delivery reports.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of delivery reports created from the request.
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $reports = [];

    $response->setStatusCode(200);

    $messageID = $request->query->get('sms_unique_id');
    $status = $request->query->get('stat');
    $error = $request->query->get('err');

    $report = new SmsDeliveryReport();

    $report->setMessageId($messageID);

    if ($status != 'DELIVRD') {
      // Message sending failed
      $report->setStatus(static::$statusMap[$status]);
      $report->setStatusMessage('Received error code: '.$error);
    } else {
      $report->setStatus(SmsMessageReportStatus::DELIVERED);
    }

    $reports[] = $report;

    return $reports;
  }

  /**
   * Gets delivery reports from the gateway.
   *
   * @param string[]|NULL $message_ids
   *   A list of specific message ID's to pull, or NULL to get any reports which
   *   have not been requested previously.
   *
   * @return \Drupal\sms\Message\SmsDeliveryReportInterface[]
   *   An array of the delivery reports which have been pulled.
   */
  public function getDeliveryReports(array $message_ids = NULL) {
    $reports = [];

    if ($message_ids == NULL) {
      //As of now we don't have a function to check all non checked messages, and Messente only keeps the delivery reports for 48 hours.
      return $reports;
    }

    foreach ($message_ids as $messageID) {
      $report = new SmsDeliveryReport();
      $report->setMessageId($messageID);

      $postArray = [];
      $postArray['form_params'] = [
        'username' => $this->configuration['user'],
        'password' => $this->configuration['pass'],
        'sms_unique_id' => $messageID,
      ];

      try {
        $response = $this->httpClient->request('post', ($this->configuration['secure']? 'https://':'http://').$this->configuration['host'].'/send_sms/', $postArray);
        // Let's ignore other codes than 200 for now
        if ($response->getStatusCode() == 200) {
          list($status, $code) = explode(' ', $response->getBody()->__toString());
          if ($status == 'OK') {
            switch ($code) {
              case 'SENT':
                $report->setStatus(SmsMessageReportStatus::QUEUED);
                break;
              case 'FAILED':
                $report->setStatus(SmsMessageReportStatus::REJECTED);
                break;
              case 'DELIVERED':
                $report->setStatus(SmsMessageReportStatus::DELIVERED);
                break;
            }
          } elseif ($status == 'FAILED') {
            $report->setStatus(SmsMessageReportStatus::QUEUED);
            $report->setStatusMessage('Received code: '.$status.' '.$code);
          } else {
            $report->setStatus(SmsMessageReportStatus::ERROR);
            $report->setStatusMessage('Received code: '.$status.' '.$code);
          }
        }
      } catch (RequestException $e) {
        $report->setStatus(SmsMessageReportStatus::ERROR);
        $report->setStatusMessage($e->getMessage());
      }

      $reports[] = $report;
    }

    return $reports;
  }
}
