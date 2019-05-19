<?php

namespace Drupal\sms_gateway_base\Tests\Plugin\SmsGateway;

use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\sms\Tests\SmsFrameworkTestTrait;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

trait GatewayTestTrait {

  use SmsFrameworkTestTrait;

  /**
   * Creates a mock Guzzle HTTP client given mock response status and body.
   *
   * This sets up the mock client to respond to the first request it gets
   * with an HTTP response containing your mock json body and status.
   *
   * @param int $mock_response_status
   *   The mock response status.
   * @param string $mock_response_body
   *   The mock response body.
   *
   * @return \GuzzleHttp\Client
   */
  public function getMockHttpClient($mock_response_status, $mock_response_body) {
    $mock    = new MockHandler([new Response($mock_response_status, [], $mock_response_body)]);
    $handler = HandlerStack::create($mock);
    return new HttpClient(['handler' => $handler]);
  }

  /**
   * Creates an SMS gateway entity with specified plugin and settings.
   *
   * @param string $plugin_id
   *   The plugin which will be used to create the gateway.
   * @param array $settings
   *   Gateway settings according to the 'sms_gateway_settings_extended' schema.
   *
   * @return \Drupal\sms\Entity\SmsGatewayInterface
   */
  public function createSmsGateway($plugin_id, $settings) {
    $settings += [
      'ssl'      => FALSE,
      'username' => '',
      'password' => '',
      'server'   => '',
      'port'     => 80,
      'reports'  => TRUE,
      'method'   => 'GET',
    ];
    $gateway  = SmsGateway::create([
      'id'       => $this->randomMachineName(),
      'label'    => $this->randomString(),
      'plugin'   => $plugin_id,
      'settings' => $settings,
    ]);
    $gateway
      ->enable()
      ->save();
    // Rebuild route to ensure delivery report url is created.
    $this->container->get('router.builder')->rebuild();
    return $gateway;
  }

  public function sendMockSms(SmsMessageInterface $sms_message, $expected_response_body, $expected_response_status = 200) {
    // Mock the http client with the desired response.
    $old_client = $this->container->get('http_client');
    $this->container->set('http_client', $this->getMockHttpClient($expected_response_status, $expected_response_body));
    $defaultSmsProvider = $this->container->get('sms.provider');
    $messages = $defaultSmsProvider->send($sms_message);
    $this->container->set('http_client', $old_client);
    return $messages;
  }

  public function simulateDeliveryReportPush(SmsGatewayInterface $gateway, $headers = [], $simulated_report = '', $query = []) {
    // Get the delivery reports url and simulate push delivery report.
    $url = $gateway->getPushReportUrl()->setAbsolute()->toString();
    $client = $this->container->get('http_client');
    return $client->request('post', $url, [
      'headers' => $headers,
      'body'    => $simulated_report,
      'query'   => $query,
    ]);
  }

}
