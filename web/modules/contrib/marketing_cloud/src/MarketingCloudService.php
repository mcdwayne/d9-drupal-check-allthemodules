<?php

namespace Drupal\marketing_cloud;

use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Client;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Swaggest\JsonSchema\Exception;

/**
 * Class MarketingCloudService.
 *
 * This is the base class for all API services in this suite.
 *
 * It encapsulate the API call functionality and interfaces with
 * MarketingCloudSession.
 *
 * @package Drupal\marketing_cloud
 */
abstract class MarketingCloudService {
  use StringTranslationTrait;

  private $configFactory;
  private $loggerFactory;
  private $httpClient;
  private $messenger;

  /**
   * MarketingCloudService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Dependency injection config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Dependency injection logger factory.
   * @param \GuzzleHttp\Client $httpClient
   *   Dependency injection REST client.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactory $loggerFactory, Client $httpClient, MessengerInterface $messenger) {
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
    $this->httpClient = $httpClient;
    $this->messenger = $messenger;
  }

  /**
   * Wrapper function to make an API call.
   *
   * This takes care of data validation, authentication and use of endpoint
   * configuration.
   *
   * @param string $moduleName
   *   The machine name of the child class's definition in its config.
   * @param string $machineName
   *   The machine name of the api call in the config.
   * @param mixed $data
   *   The payload for the body. This can be object, array or simple type.
   * @param array $urlParams
   *   Name/value pairs for token replacement in the URI defined in the config.
   * @param array $params
   *   Name/value pairs for extra arguments to be added to the URI,
   *   e.g. ['foo' => 'var'] gives &foo=bar.
   *
   * @throws Exception
   *
   * @return array|bool|int|mixed|string
   *   The result of the API call or FALSE on failure.
   *
   * @see restCall()
   */
  protected function apiCall($moduleName, $machineName, $data, array $urlParams = [], array $params = []) {
    $marketingCloudConfig = $this->configFactory->getEditable('marketing_cloud.settings');
    $validateJson = $marketingCloudConfig->get('validate_json');
    $doNotSend = $marketingCloudConfig->get('do_not_send');
    // Create module settings path string.
    // This assumes all sub-modules will name their settings file:
    // <module_name>.settings.yml.
    $subModuleSettingsPath = "$moduleName.settings";
    $subModuleConfig = $this->configFactory->getEditable($subModuleSettingsPath);

    // Ensure correct object/array types for schema, e.g. associative arrays
    // are converted to objects.
    $data = json_decode(json_encode($data));
    if ($data === NULL && json_last_error() !== JSON_ERROR_NONE) {
      $message = $this->t('Could not send %machine_name, invalid JSON data',
        ['%machine_name' => $machineName]
      );
      $this->messenger->addError($message);
      $this->loggerFactory->get(__METHOD__)->error($message);
      return FALSE;
    }

    // Fetch method.
    $method = $subModuleConfig->get("definitions.$machineName.method");
    if (empty($method)) {
      $message = $this->t('Could not fetch the method for %machine_name. Please check the configuration: %module_name.',
        [
          '%machine_name' => $machineName,
          '%module_name' => $moduleName,
        ]
      );
      $this->messenger->addError($message);
      $this->loggerFactory->get(__METHOD__)->error($message);
      return FALSE;
    }

    // Fetch endpoint.
    $endpoint = $subModuleConfig->get("definitions.$machineName.endpoint");
    if (empty($endpoint)) {
      $message = $this->t('Could not fetch the endpoint for %machine_name. Please check the configuration: %module_name.',
        [
          '%machine_name' => $machineName,
          '%module_name' => $moduleName,
        ]
      );
      $this->messenger->addError($message);
      $this->loggerFactory->get(__METHOD__)->error($message);
      return FALSE;
    }

    if ($validateJson) {
      // Fetch endpoint JSON schema.
      $schema = $subModuleConfig->get("definitions.$machineName.schema");
      if (empty($schema)) {
        $message = $this->t('Could not fetch the schema for %machine_name. Please check the configuration: %module_name.',
          [
            '%machine_name' => $machineName,
            '%module_name' => $moduleName,
          ]
        );
        $this->messenger->addError($message);
        $this->loggerFactory->get(__METHOD__)->error($message);
        return FALSE;
      }

      // Decode the JSON schema for validation use.
      $schema = json_decode($schema);
      if ($schema === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $message = $this->t('Could not decode the schema for %machine_name. Please check the configuration: %module_name.',
          [
            '%machine_name' => $machineName,
            '%module_name' => $moduleName,
          ]
        );
        $this->messenger->addError($message);
        $this->loggerFactory->get(__METHOD__)->error($message);
        return FALSE;
      }

      // Load the JSON Schema.
      try {
        $validator = Schema::import($schema);
      }
      catch (Exception $e) {
        $message = $this->t('Errors were found in the schema for %machine_name in %module_name. Please check the logs.', ['%machine_name' => $machineName, '%module_name' => $moduleName]);
        $this->messenger->addError($message);
        $message = $this->t('Error in the JSON schema for the %machine_name in %module_name schema: %error',
          [
            '%machine_name' => $machineName,
            '%module_name' => $moduleName,
            '%error' => $e->getMessage(),
          ]
        );
        $this->messenger->addError($message);
        $this->loggerFactory->get(__METHOD__)->error($message);
        return FALSE;
      }
      catch (InvalidValue $e) {
        $message = $this->t('Errors were found in the schema for %machine_name in %module_name. Please check the logs.', ['%machine_name' => $machineName, '%module_name' => $moduleName]);
        $this->messenger->addError($message);
        $message = $this->t('Error in the JSON schema for the %machine_name in %module_name schema: %error',
          [
            '%machine_name' => $machineName,
            '%module_name' => $moduleName,
            '%error' => $e->getMessage(),
          ]
        );
        $this->messenger->addError($message);
        $this->loggerFactory->get(__METHOD__)->error($message);
        return FALSE;
      }

      // Validate the JSON against the Schema.
      try {
        $validator->in($data);
      }
      catch (Exception $e) {
        $message = $this->t('Data error against the schema: %error', ['%error' => $e->getMessage()]);
        $this->messenger->addError($message);
        $this->loggerFactory->get(__METHOD__)->error($message);
        return FALSE;
      }
    }

    // Create endpoint URL with any required params.
    foreach ($urlParams as $key => $val) {
      $endpoint = str_replace($key, $val, $endpoint);
    }
    $arr = [];
    foreach ($params as $key => $val) {
      $arr[] = "$key=$val";
    }
    if (count($arr) > 0) {
      $endpoint = $endpoint . '?' . implode('&', $arr);
    }
    $url = $marketingCloudConfig->get('base_url') . $endpoint;

    // Prepare data.
    $data = json_encode($data);

    // Special case for testing - do not send the api request,
    // but instead return the URL and data that would be sent.
    if ($doNotSend) {
      return ['url' => $url, 'data' => $data, 'method' => $method];
    }

    // Fetch the token.
    $response = $this->restCall($method, $url, $data);
    if ($response == '401 Unauthorized') {
      $this->loggerFactory->get(__METHOD__)->notice('Stale token, fetching a fresh token and resending');
      $response = $this->restCall($method, $url, $data, TRUE);
    }

    return $response;
  }

  /**
   * Utility function to send a single request to MarketingCloud.
   *
   * @param string $method
   *   GET, POST, DELETE, PUT.
   * @param string $url
   *   The endpoint URL.
   * @param string $data
   *   The JSON payload string.
   * @param bool $force
   *   TRUE = always fetch a fresh token.
   *   FALSE = use the existing token, or fetch a fresh if stale.
   *
   * @return bool|int|mixed|string
   *   Return the API call result or FALSE on failure.
   *
   * @see apiCall()
   */
  private function restCall($method, $url, $data, $force = FALSE) {
    // Fetch authentication token.
    $session = new MarketingCloudSession();
    $token = $session->token($force);

    if (!$token) {
      $message = $this->t('%method to %url failed, unable to fetch authentication token', ['%method' => $method, '%url' => $url]);
      $this->messenger->addError($message);
      $this->loggerFactory->get(__METHOD__)->error($message);
      return FALSE;
    }

    // Send request to endpoint.
    $response = FALSE;
    try {
      $options = [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => "Bearer $token",
        ],
      ];
      if (!empty($data)) {
        $options['body'] = $data;
      }
      $raw = $this->httpClient->{$method}($url, $options);
      $response = json_decode($raw->getBody(), TRUE);
    }
    catch (RequestException $e) {
      $message = $this->t('%error', ['%error' => $e->getMessage()]);
      $this->loggerFactory->get(__METHOD__)->error(json_encode($message));
      // Response code may sometimes contain the reason text.
      $code = $e->getResponse()->getStatusCode();
      $reason = $e->getResponse()->getReasonPhrase();
      $response = (strpos($code, $reason) === FALSE) ? "$code $reason" : $code;
    }
    catch (\Exception $e) {
      $message = $this->t('%error', ['%error' => $e->getMessage()]);
      $this->loggerFactory->get(__METHOD__)->error(json_encode($message));
    }

    return $response;
  }

}
