<?php

namespace Drupal\sms_smsfactor\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin handles sending SMSes through SMSFactor SMS API.
 *
 * @SmsGateway(
 *   id = "smsfactor",
 *   label = @Translation("SMSFactor"),
 *   outgoing_message_max_recipients = 1,
 *   incoming = FALSE,
 *   incoming_route = FALSE,
 * )
 */
class SMSFactorGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

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

    $form['send']['token'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('SMSFactor Token'),
      '#description' => $this->t('Your token.'),
      '#default_value' => $this->configuration['token'],
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
    $this->configuration['token'] = trim($form_state->getValue([
      'send',
      'token',
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

    $endpoint = 'https://api.smsfactor.com/send';
    $token = $this->configuration['token'];

    $postdata = [
      'sms' => [
        'authentication' => [
          'token' => $token,
        ],
        'message' => [
          'text' => $sms->getMessage(),
        ],
        'recipients' => ['gsm' => [['value' => $sms->getRecipients()[0]]]],
      ],
    ];

    try {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $endpoint);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
      $response = json_decode(curl_exec($ch));
      curl_close($ch);

      if (!$response->error) {
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
          ->setErrorMessage($response->error);
      }
    }
    catch (Exception $e) {
      return $result
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage($e->getMessage());
    }

  }

}
