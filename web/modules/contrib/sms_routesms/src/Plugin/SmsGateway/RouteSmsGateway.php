<?php

namespace Drupal\sms_routesms\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms_gateway_base\Plugin\SmsGateway\GatewayCommand;
use Drupal\sms_gateway_base\Plugin\SmsGateway\DefaultGatewayPluginBase;
use Drupal\sms_gateway_base\Plugin\SmsGateway\InvalidCommandException;
use Drupal\sms_routesms\Plugin\SmsGateway\RouteSms\DeliveryReportHandler;
use Drupal\sms_routesms\Plugin\SmsGateway\RouteSMS\MessageResponseHandler;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds support for sending SMS messages using the RouteSMS gateway.
 *
 * @SmsGateway(
 *   id = "routesms",
 *   label = @Translation("RouteSMS Gateway"),
 *   outgoing_message_max_recipients = 400,
 *   schedule_aware = FALSE,
 *   reports_push = TRUE,
 *   credit_balance_available = FALSE
 * )
 */
class RouteSmsGateway extends DefaultGatewayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getCreditsBalance() {
    // RouteSMS does not yet implement credit balance.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHttpParametersForCommand($command, array $data, array $config) {
    $method = 'GET';
    $body = '';
    $query = [];
    $headers = [];

    // Setup username and password.
    $query['username'] = $config['username'];
    $query['password'] = $config['password'];
    $query['type'] = empty($data['isflash']) ? 0 : 1;
    // Delivery report push url. The standard format of the API is as below.
    // http://ip/app/status?unique_id=%7&reason=%2&to=%p&from=%P&time=%t&status=%d
    // http://<hostname>:Port/example.php?sender=%P&mobile=%p&dtSent=%t&msgid=%I&status=%d
    if ($command == GatewayCommand::SEND && $config['reports'] && isset($data['options']['delivery_report_url']) && $dlr_url = $data['options']['delivery_report_url']) {
      $query['dlr'] = 1;
      // @todo Do we need the additional query args. It's not in their API doc.
      $query['dlr-url'] = urlencode($dlr_url);
    }

    switch ($command) {
      case GatewayCommand::SEND:
        $query['destination'] = implode(',', $data['recipients']);
        $query['source'] = $data['sender'];
        $query['message'] = $data['message'];
        break;

      case GatewayCommand::TEST:
        $query['dlr'] = 0;
        $query['destination'] = $config['test_number'];
        $query['source'] = \Drupal::config('system.site')->get('name');
        $query['message'] = 'Configuration+Successful';
        break;

      default:
        throw new InvalidCommandException('Invalid command ' . $command);
    }
    return [
      'query' => $query,
      'method' => $method,
      'headers' => $headers,
      'body' => $body,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function handleResponse(ResponseInterface $response, $command, array $data, array $config) {
    // Check for HTTP errors.
    if ($response->getStatusCode() !== 200) {
      $this->errors[] = [
        'code' => $response->getStatusCode(),
        'message' => $response->getReasonPhrase(),
      ];
      return (new SmsMessageResult())
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage($this->t('An error occurred during the HTTP request: @error',
          ['@error' => $response->getReasonPhrase()]));
    }

    // Call the message response handler to handle the message response.
    if ($body = (string) $response->getBody()) {
      $handler = new MessageResponseHandler([$config['test_number']]);
      return $handler->handle($body);
    }

    // @todo This needs test coverage.
    return (new SmsMessageResult())
      ->setError(SmsMessageResultStatus::ERROR)
      ->setErrorMessage($this->t('No content received from the gateway'));
  }

  /**
   * Provides the web resource or path that corresponds to a command.
   *
   * @param string $command
   *   The command for which a web resource or path is needed.
   *
   * @return string
   */
  protected function getResourceForCommand($command) {
    switch ($command) {
      case GatewayCommand::SEND:
      case GatewayCommand::TEST:
        return '/bulksms/bulksms';

      default:
        throw new InvalidCommandException('Invalid command ' . $command);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $handler = new DeliveryReportHandler();
    return $handler->parseDeliveryReport($request->query->all())->getReports();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'test_number' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['settings']['test_number'] = array(
      '#type' => 'number',
      '#title' => t('Test Number'),
      '#description' => t('A number to confirm configuration settings. You will receive an sms if the settings are ok.'),
      '#size' => 30,
      '#maxlength' => 64,
      '#default_value' => $this->configuration['test_number'],
      '#min' => 1,
    );

    return $form;
  }

}
