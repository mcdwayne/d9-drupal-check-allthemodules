<?php

namespace Drupal\sms_gateway_test\Plugin\SmsGateway;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms_gateway_base\Plugin\SmsGateway\DefaultGatewayPluginBase;
use Drupal\sms_gateway_base\Plugin\SmsGateway\InvalidCommandException;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;

/**
 * Adds a gateway to communicate with foo llamas.
 *
 * @SmsGateway(
 *   id = "foo_llama",
 *   label = @Translation("Foo Llama Gateway"),
 * )
 */
class FooLlamaGateway extends DefaultGatewayPluginBase {
  
  protected $simulate = [];

  /**
   * {@inheritdoc}
   */
  public function getCreditsBalance() {
    return 10.0;
  }

  /**
   * Test invalid command to the gateway.
   */
  public function doInvalidCommand() {
    return $this->doCommand('invalid', []);
  }

  /**
   * {@inheritdoc}
   */
  protected function getHttpParametersForCommand($command, array $data, array $config) {
    if ($command === 'invalid') {
      throw new InvalidCommandException('Invalid command ' . $command);
    }
    // Setup for simulation of gateway connection error.
    $this->simulate = $config['simulate_error'];
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function httpRequest($url, $query = [], $method = 'GET', $headers = [], $body = '') {
    if ($this->simulate['message']) {
      // Throw a Guzzle exception to simulate a gateway error.
      throw new TransferException($this->simulate['message'], $this->simulate['code']);
    }
    else {
      return parent::httpRequest($url, $query, $method, $headers, $body);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function handleResponse(ResponseInterface $response, $command, array $data, array $config) {
    // Check for HTTP errors.
    if ($response->getStatusCode() !== 200) {
      // Add this to the errors list.
      $this->errors[] = [
        'code' => $response->getStatusCode(),
        'message' => $response->getReasonPhrase(),
      ];
      return (new SmsMessageResult())
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage(new FormattableMarkup('HTTP response error (@code) @message',
          ['@code' => $response->getStatusCode(), '@message' => $response->getReasonPhrase()]));
    }
    else {
      // Verify the response from example.com.
      if ($this->verifyResponse($response->getBody())) {
        // The response was correctly verified.
        return (new SmsMessageResult())
          ->setErrorMessage('The expected response "Example Domain This domain is established to be used for illustrative examples in documents." was found');
      }
      else {
        // Return false if the response did not verify.
        return (new SmsMessageResult())
          ->setError(SmsMessageResultStatus::ERROR)
          ->setErrorMessage('The expected response "Example Domain This domain is established to be used for illustrative examples in documents." was not found');
      }
    }
  }

  /**
   * Verifies the response.
   *
   * @param string $body
   *   The body text to verify.
   *
   * @return bool
   *   True if the body contains the specific response.
   */
  protected function verifyResponse($body) {
    return strpos($body, 'This domain is established to be used for illustrative examples in documents.') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getResourceForCommand($command) {
    return '/';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['settings']['simulate_error'] = [
      '#type' => 'detail',
      '#title' => 'Simulate Error',
      '#tree' => TRUE,
    ];
    $form['settings']['simulate_error']['code'] = [
      '#type' => 'number',
      '#title' => 'Error Code',
      '#default_value' => '0',
    ];
    $form['settings']['simulate_error']['message'] = [
      '#type' => 'textfield',
      '#title' => 'Error Message',
      '#default_value' => '',
    ];

    return $form;
  }

}
