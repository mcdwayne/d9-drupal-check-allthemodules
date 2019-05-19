<?php

namespace Drupal\sms_infobip\Plugin\SmsGateway;

use Drupal\Component\Serialization\Json;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms_gateway_base\Plugin\SmsGateway\GatewayCommand;
use Drupal\sms_gateway_base\Plugin\SmsGateway\InvalidCommandException;
use Drupal\sms_infobip\Plugin\SmsGateway\Infobip\CreditBalanceResponseHandler;
use Drupal\sms_infobip\Plugin\SmsGateway\Infobip\DeliveryReportHandler;
use Drupal\sms_infobip\Plugin\SmsGateway\Infobip\MessageResponseHandler;
use Drupal\sms_gateway_base\Plugin\SmsGateway\DefaultGatewayPluginBase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds support for sending SMS messages using the Infobip gateway.
 *
 * @SmsGateway(
 *   id = "infobip",
 *   label = @Translation("Infobip Gateway"),
 *   outgoing_message_max_recipients = 400,
 *   schedule_aware = FALSE,
 *   reports_push = TRUE,
 *   credit_balance_available = TRUE
 * )
 */
class InfobipGateway extends DefaultGatewayPluginBase {

  // Infobip server API endpoints / resources.
  const ENDPOINT_SEND_ADVANCED   = '/sms/1/text/advanced';
  const ENDPOINT_DELIVERY_REPORT = '/sms/1/reports';
  const ENDPOINT_CREDIT_BALANCE  = '/account/1/balance';

  /**
   * {@inheritdoc}
   */
  protected function getHttpParametersForCommand($command, array $data, array $config) {
    $method = 'GET';
    $body = '';
    $query = [];
    // Set up common headers for the REST request.
    $headers = [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
      'Authorization' => 'Basic ' . base64_encode("{$config['username']}:{$config['password']}")
    ];

    switch ($command) {
      case GatewayCommand::SEND:
        // Method is POST for send requests.
        $method = 'POST';
        // Turn the recipient array to the format understood by Infobip.
        $message['destinations'] = array_map(function($recipient) {
          return ['to' => $recipient];
        }, $data['recipients']);
        $message['from'] = $data['sender'];
        $message['text'] = $data['message'];
        $message['flash'] = (bool) $data['isflash'];
        // Configure push delivery reports if URL is specified.
        if ($this->configuration['reports'] && isset($data['options']['delivery_report_url'])) {
          $message['notifyUrl'] = $data['options']['delivery_report_url'];
          $message['notifyContentType'] = 'application/json';
        }
        // Set the body to JSON encoded data.
        $body = Json::encode([
          'bulkId' => $this->randomMessageID(),
          'messages' => [$message],
        ]);
        break;

      case GatewayCommand::REPORT:
        // Method is GET for delivery reports pulling.
        $method = 'GET';
        if (isset($data['message_ids'])) {
          if (is_array($data['message_ids'])) {
            $data['message_ids'] = implode(',', $data['message_ids']);
          }
          $query['messageId'] = $data['message_ids'];
        }
        if (isset($data['bulkId'])) {
          $query['bulkId'] = $data['bulkId'];
        }
        break;

      case GatewayCommand::BALANCE:
      case GatewayCommand::TEST:
        // Really nothing to do here.
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
        ->setErrorMessage($this->t('An error occurred during the HTTP request: (@code) @message',
          ['@code' => $response->getStatusCode(), '@message' => $response->getReasonPhrase()]));
    }
    else {
      if ($command == 'test') {
        // No need for further processing if it was just a gateway test.
        return new SmsMessageResult();
      }
    }

    // Check for Infobip errors. Because Infobip responses (including error
    // codes) are different for each endpoint (i.e. API resource) called, we have
    // to implement different response handlers for each endpoint.
    $result = [];
    if ($body = (string) $response->getBody()) {
      switch ($this->getResourceForCommand($command)) {
        case self::ENDPOINT_SEND_ADVANCED:
          $handler = new MessageResponseHandler();
          $result = $handler->handle($body);
          break;

        case self::ENDPOINT_CREDIT_BALANCE:
          $handler = new CreditBalanceResponseHandler();
          $result = $handler->handle($body);
          break;

        case self::ENDPOINT_DELIVERY_REPORT: // Fallthrough
        default:
          $handler = new DeliveryReportHandler();
          $result = $handler->handle($body);
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getResourceForCommand($command, $method = 'GET') {
    switch ($command) {
      case GatewayCommand::SEND:
        return self::ENDPOINT_SEND_ADVANCED;
      case GatewayCommand::REPORT:
        return self::ENDPOINT_DELIVERY_REPORT;
      case GatewayCommand::BALANCE:
      case GatewayCommand::TEST:
        return self::ENDPOINT_CREDIT_BALANCE;
      default:
        throw new InvalidCommandException('Invalid command ' . $command);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parseDeliveryReports(Request $request, Response $response) {
    $handler = new DeliveryReportHandler();
    return $handler->handle($request->getContent())->getReports();
  }

}
