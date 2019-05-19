<?php

namespace Drupal\sms_simplegateway\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\SmsProcessingResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This plugin handles sending SMSes through most GET & POST based SMS Gateways.
 *
 * @SmsGateway(
 *   id = "simplegateway",
 *   label = @Translation("Simple Gateway"),
 *   outgoing_message_max_recipients = 1,
 *   incoming = TRUE,
 *   incoming_route = TRUE,
 * )
 */
class SimpleGateway extends SmsGatewayPluginBase implements ContainerFactoryPluginInterface {

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
    $form['send']['method'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('HTTP method'),
      '#default_value' => $this->configuration['method'],
      '#options' => [
        'get' => 'GET',
        'post' => 'POST',
      ],
    ];
    $form['send']['authorization'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Authorization'),
      '#description' => $this->t('For POST Requests Only. use "Username field value" & "Password field value" to specify credentials'),
      '#default_value' => $this->configuration['authorization'],
      '#options' => [
        'none' => 'NONE',
        'basic' => 'BASIC',
        'digest' => 'DIGEST',
        'ntlm' => 'NTLM',
      ],
    ];
    $form['send']['content_type'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Content Encoding'),
      '#description' => $this->t('For POST Requests Only.'),
      '#default_value' => $this->configuration['content_type'],
      '#options' => [
        'plain' => 'Plain',
        'json' => 'Json',
      ],
    ];

    $form['send']['base_url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Base URL for sending messages'),
      '#description' => $this->t('Eg: http://simplegateway.example.com:13031/sendsms'),
      '#default_value' => $this->configuration['base_url'],
    ];
    $form['send']['user_field'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Username field name'),
      '#description' => $this->t('The argument/field name for the field that holds the username. Eg: user, username, authid.'),
      '#default_value' => $this->configuration['user_field'],
    ];
    $form['send']['user_value'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Username field value'),
      '#description' => $this->t('Your username for this gateway account.'),
      '#default_value' => $this->configuration['user_value'],
    ];
    $form['send']['pass_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password field name'),
      '#description' => $this->t('Optional. The argument/field name for the field that holds the password. Eg: pass, password, passwd.'),
      '#default_value' => $this->configuration['pass_field'],
    ];
    $form['send']['pass_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password field value'),
      '#description' => $this->t('Optional. Your password for this gateway account.'),
      '#default_value' => $this->configuration['pass_value'],
    ];
    $form['send']['sender_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender (from) field name'),
      '#description' => $this->t('The argument/field name for the field that holds the sender number data. Eg: from, sender'),
      '#default_value' => $this->configuration['sender_field'],
    ];
    $form['send']['sender_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender (from) field value'),
      '#description' => $this->t('The FROM number/name the sms should go out having'),
      '#default_value' => $this->configuration['sender_value'],
    ];
    $form['send']['number_field'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Number (to) field name'),
      '#description' => $this->t('The argument/field name for the field that holds the number data. Eg: number, to, no'),
      '#default_value' => $this->configuration['number_field'],
    ];
    $form['send']['number_prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Number (to) prefix value'),
      '#description' => t('The value to be prefixed to the sender. Eg: country code'),
      '#default_value' => $this->configuration['number_prefix'],
    ];
    $form['send']['message_field'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Message field name'),
      '#description' => $this->t('The argument/field name for the field that holds the message text. Eg: message, text, content'),
      '#default_value' => $this->configuration['message_field'],
    ];
    $form['send']['extra_params'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra parameters'),
      '#description' => $this->t('Any additional parameters that the gateway may need. Eg: route=4&country=0'),
      '#default_value' => $this->configuration['extra_params'],
    ];

    $form['receive']['receive_number_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender (from) field name'),
      '#description' => $this->t('The argument/field name for the field that holds the sender number. Eg: sender, from.'),
      '#default_value' => $this->configuration['receive_number_field'],
      '#group' => 'incoming_messages',
    ];
    $form['receive']['receive_message_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message field name'),
      '#description' => $this->t('The argument/field name for the field that holds the message. Eg: message, text, content.'),
      '#default_value' => $this->configuration['receive_message_field'],
      '#group' => 'incoming_messages',
    ];
    $form['receive']['receive_gwnumber_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Receiver (to) field name'),
      '#description' => $this->t('Optional. The argument/field name for the field that holds the gateway receiver number. Eg: to, inNumber, receiver.'),
      '#default_value' => $this->configuration['receive_gwnumber_field'],
      '#group' => 'incoming_messages',
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
    $this->configuration['method'] = trim($form_state->getValue([
      'send',
      'method',
    ]));
    $this->configuration['authorization'] = trim($form_state->getValue([
      'send',
      'authorization',
    ]));
    $this->configuration['content_type'] = trim($form_state->getValue([
      'send',
      'content_type',
    ]));
    $this->configuration['base_url'] = trim($form_state->getValue([
      'send',
      'base_url',
    ]));
    $this->configuration['user_field'] = trim($form_state->getValue([
      'send',
      'user_field',
    ]));
    $this->configuration['user_value'] = trim($form_state->getValue([
      'send',
      'user_value',
    ]));
    $this->configuration['pass_field'] = trim($form_state->getValue([
      'send',
      'pass_field',
    ]));
    $this->configuration['pass_value'] = trim($form_state->getValue([
      'send',
      'pass_value',
    ]));
    $this->configuration['sender_field'] = trim($form_state->getValue([
      'send',
      'sender_field',
    ]));
    $this->configuration['sender_value'] = trim($form_state->getValue([
      'send',
      'sender_value',
    ]));
    $this->configuration['number_field'] = trim($form_state->getValue([
      'send',
      'number_field',
    ]));
    $this->configuration['number_prefix'] = trim($form_state->getValue([
      'send',
      'number_prefix',
    ]));
    $this->configuration['message_field'] = trim($form_state->getValue([
      'send',
      'message_field',
    ]));
    $this->configuration['extra_params'] = trim($form_state->getValue([
      'send',
      'extra_params',
    ]));

    $this->configuration['receive_number_field'] = trim($form_state->getValue('receive_number_field'));
    $this->configuration['receive_message_field'] = trim($form_state->getValue('receive_message_field'));
    $this->configuration['receive_gwnumber_field'] = trim($form_state->getValue('receive_gwnumber_field'));
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

    $client = \Drupal::httpClient();
    $result = new SmsMessageResult();
    $report = new SmsDeliveryReport();

    $parameters = array_filter([
      $this->configuration['user_field'] => $this->configuration['user_value'],
      $this->configuration['pass_field'] => $this->configuration['pass_value'],
      $this->configuration['sender_field'] => $this->configuration['sender_value'],
      $this->configuration['number_field'] => $this->configuration['number_prefix'] . $sms->getRecipients()[0],
      $this->configuration['message_field'] => $sms->getMessage(),
    ]);

    $url = NULL;
    $options = [];
    switch ($this->configuration['method']) {

      case 'get':
        $url = $this->configuration['base_url'] .
          '?' . http_build_query($parameters) .
          '&' . $this->configuration['extra_params'];
        break;

      case 'post':
        $url = $this->configuration['base_url'];
        switch ($this->configuration['authorization']) {
          case 'basic':
            $options['auth'] = [
              $this->configuration['user_value'],
              $this->configuration['pass_value'],
            ];
            break;
          case 'digest':
            $options['auth'] = [
              $this->configuration['user_value'],
              $this->configuration['pass_value'],
              'digest',
            ];
            break;
          case 'ntlm':
            $options['auth'] = [
              $this->configuration['user_value'],
              $this->configuration['pass_value'],
              'ntlm',
            ];
            break;
        }
        switch ($this->configuration['content_type']) {
          case 'plain':
            $options['headers'] = ['Content-Type' => 'application/x-www-form-urlencoded'];
            $options['body'] = http_build_query($parameters) . '&' . $this->configuration['extra_params'];
            break;
          case 'json':
            $options['headers'] = ['Content-Type' => 'application/json'];
            $options['body'] = json_encode($parameters);
            break;
            break;
        }
    }

    try {
      $response = $client->request($this->configuration['method'], $url, $options);
      if ($response->getStatusCode() >= 200 and $response->getStatusCode() <= 299) {
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
    } catch (HttpException $e) {
      return $result
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage($e->getMessage());
    }

  }

  /**
   * Process an incoming message POST request.
   *
   * This callback expects a 'messages' POST value containing JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\sms\Entity\SmsGatewayInterface $sms_gateway
   *   The gateway instance.
   *
   * @return \Drupal\sms\SmsProcessingResponse
   *   A SMS processing response task.
   */
  public function processIncoming(Request $request, SmsGatewayInterface $sms_gateway) {

    $number = $request->get($this->configuration['receive_number_field']);
    $text = $request->get($this->configuration['receive_message_field']);
    $gwnumber = $request->get($this->configuration['receive_gwnumber_field']);

    $report = (new SmsDeliveryReport())
      ->setRecipient($gwnumber)
      ->setTimeDelivered(\Drupal::time()->getRequestTime());

    $result = (new SmsMessageResult())
      ->setReports([$report]);

    $message = (new SmsMessage())
      ->addRecipient($gwnumber)
      ->setMessage($text)
      ->setSender($number)
      ->setResult($result)
      ->setGateway($sms_gateway)
      ->setDirection(Direction::INCOMING);

    $response = new Response('', 200);
    $task = (new SmsProcessingResponse())
      ->setResponse($response)
      ->setMessages([$message]);

    return $task;
  }

}
