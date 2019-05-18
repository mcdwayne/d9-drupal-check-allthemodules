<?php

namespace Drupal\sms_ovh\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Ovh\Api;

/**
 * This plugin handles sending SMSes through OVH SMS API.
 *
 * @SmsGateway(
 *   id = "ovh",
 *   label = @Translation("OVH"),
 *   outgoing_message_max_recipients = 1,
 *   incoming = FALSE,
 *   incoming_route = FALSE,
 * )
 */
class OVHGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Builds the configuration form.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['send'] = [
      '#type' => 'details',
      '#title' => $this->t('Outgoing Messages'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['send']['endpoint'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t('OVH Endpoint'),
      '#options' => [
        'ovh-eu' => 'OVH EU',
        'ovh-ca' => 'OVH CA',
        'ovh-us' => 'OVH US',
        'kimsufi-eu' => 'KIMSUFI EU',
        'kimsufi-ca' => 'KIMSUFI CA',
        'soyoustart-eu' => 'SOYOUSTART EU',
        'soyoustart-ca' => 'SOYOUSTART CA',
        'runabove-ca' => 'RUNABOVE CA',
      ],
      '#default_value' => $this->configuration['endpoint'],
    ];
    $form['send']['application_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application Key'),
      '#description' => $this->t('The application key obtained on <a href="@url">createToken page</a>.', ['@url' => 'https://api.ovh.com/createToken/index.cgi?GET=/sms&GET=/sms/%2a&PUT=/sms/%2a&DELETE=/sms/%2a&POST=/sms/%2a']),
      '#default_value' => $this->configuration['application_key'],
    ];
    $form['send']['application_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application Secret'),
      '#description' => $this->t('Your application secret.'),
      '#default_value' => $this->configuration['application_secret'],
    ];
    $form['send']['consumer_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Consumer Key'),
      '#description' => $this->t('Your consumer key.'),
      '#default_value' => $this->configuration['consumer_key'],
    ];

    return $form;
  }

  /**
   * Saves the configuration values.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['endpoint'] = trim($form_state->getValue([
      'send',
      'endpoint',
    ]));
    $this->configuration['application_key'] = trim($form_state->getValue([
      'send',
      'application_key',
    ]));
    $this->configuration['application_secret'] = trim($form_state->getValue([
      'send',
      'application_secret',
    ]));
    $this->configuration['consumer_key'] = trim($form_state->getValue([
      'send',
      'consumer_key',
    ]));
  }

  /**
   * Sends out the sms by hitting the gateway.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The sms to be sent out to the user.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   A response object indicating whether the sms was sent or not.
   */
  public function send(SmsMessageInterface $sms) {
    $result = new SmsMessageResult();
    $report = new SmsDeliveryReport();

    $endpoint = $this->configuration['endpoint'];
    $applicationKey = $this->configuration['application_key'];
    $applicationSecret = $this->configuration['application_secret'];
    $consumer_key = $this->configuration['consumer_key'];

    $conn = new Api($applicationKey,
                        $applicationSecret,
                        $endpoint,
                        $consumer_key);

    $smsServices = $conn->get('/sms/');

    $content = (object) [
      "charset" => "UTF-8",
      "class" => "phoneDisplay",
      "coding" => "7bit",
      "message" => $sms->getMessage(),
      "noStopClause" => FALSE,
      "priority" => "high",
      "receivers" => [$sms->getRecipients()[0]],
      "senderForResponse" => TRUE,
      "validityPeriod" => 2880,
    ];

    try {
      $response = $conn->post('/sms/' . $smsServices[0] . '/jobs/', $content);
      if ($response['totalCreditsRemoved'] >= 1) {
        return $result->addReport($report
          ->setRecipient($sms->getRecipients()[0])
          ->setStatus(SmsMessageReportStatus::QUEUED)
        );
      }
      else {
        return $result
          ->addReport($report
            ->setRecipient($sms->getRecipients()[0])
            ->setStatus(SmsMessageResultStatus::ERROR)
          )
          ->setErrorMessage($response->getBody());
      }
    }
    catch (ApiException $e) {
      return $result
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage($e->getMessage());
    }

  }

}
