<?php

namespace Drupal\tag1quo;

use Drupal\tag1quo\Adapter\Core\Core;
use Drupal\tag1quo\Adapter\Http\JsonResponse;

/**
 * Class Heartbeat.
 *
 * @internal This class is subject to change.
 */
class Heartbeat {

  /**
   * Error level - Heartbeat disabled.
   */
  const ERROR_DISABLED = 10;

  /**
   * Error level = Heartbeat frequency is too soon.
   */
  const ERROR_TOO_SOON = 20;

  /**
   * Error level - API token missing.
   */
  const ERROR_TOKEN_MISSING = 30;

  /**
   * Error level - API token invalid.
   */
  const ERROR_TOKEN_INVALID = 40;

  /**
   * Error level - Server response.
   */
  const ERROR_SERVER = 50;

  /**
   * Error level - Server response, API token invalid.
   */
  const ERROR_SERVER_TOKEN_INVALID = 51;

  /**
   * Error level - Unknown error.
   */
  const ERROR_UNKNOWN = 60;

  /**
   * Frequency of Heartbeat that's sent, ~once a day (1 day minus ten minutes).
   *
   * @todo Allow this to be configurable?
   */
  const FREQUENCY = 85800;

  /**
   * The API token.
   *
   * @var string
   */
  protected $apiToken;

  /**
   * Tag1 Quo settings config.
   *
   * @var \Drupal\tag1quo\Adapter\Config\Config
   */
  protected $config;

  /**
   * The Core adapter.
   *
   * @var \Drupal\tag1quo\Adapter\Core\Core
   */
  protected $core;

  /**
   * The data to be sent to the Tag1 Quo server.
   *
   * @var array
   */
  protected $data;

  /**
   * Flag indicating whether in debug mode.
   *
   * @var bool
   */
  protected $debugMode;

  /**
   * Flag indicating whether Heartbeat should be sent to the Tag1 Quo server.
   *
   * @var bool
   */
  protected $enabled;

  /**
   * The set error code, if any.
   *
   * @var int
   */
  protected $error;

  /**
   * The set error message, if any.
   *
   * @var string
   */
  protected $errorMessage;

  /**
   * Flag indicating whether to show a message after sending to the server.
   *
   * @var bool
   */
  protected $showMessage;

  /**
   * Flag indicating whether heartbeat is stale.
   *
   * @var bool
   */
  protected $stale;

  /**
   * Flag indicating whether to use cURL.
   *
   * @var bool
   */
  protected $useCurl;

  /**
   * Heartbeat constructor.
   *
   * @param \Drupal\tag1quo\Adapter\Core\Core $core
   *   The Core adapter instance.
   */
  public function __construct(Core $core = NULL) {
    $this->core = $core ?: Core::create();
    $this->config = $this->core->config('tag1quo.settings');
  }

  /**
   * Creates a new Heartbeat instance.
   *
   * @param \Drupal\tag1quo\Adapter\Core\Core $core
   *   The Core adapter instance.
   *
   * @return static
   */
  public static function create(Core $core = NULL) {
    return new static($core);
  }

  /**
   * Creates a Heartbeat that is used in scenarios where it's manually sent.
   *
   * @return static
   */
  public static function manual() {
    return static::create()
      ->setEnabled(TRUE)
      ->setShowMessage(TRUE)
      ->setStale(TRUE);
  }

  /**
   * Creates an error response.
   *
   * @param string $message
   *   The error message.
   * @param int $statusCode
   *   The status code, defaults to 500.
   * @param array $headers
   *   Optional. Additional headers to set on the response.
   *
   * @return \Drupal\tag1quo\Adapter\Http\JsonResponse
   */
  protected function errorResponse($message, $statusCode = 500, array $headers = array()) {
    return new JsonResponse($this->core->jsonEncode(array(
      'message' => $message,
    )), $statusCode, $headers);
  }

  /**
   * Retrieves the set API token.
   *
   * @return string
   */
  public function getApiToken() {
    if ($this->apiToken === NULL) {
      $this->apiToken = $this->core->getApiToken();
    }
    return $this->apiToken;
  }

  /**
   * Retrieves the data to send.
   *
   * @return array
   */
  public function getData() {
//    'title' => 'name',
//    'field_base_url' => 'baseUri',
//    'field_db_url_hash' => 'dbUrlHash',
//    'field_enable_timestamp' => 'enabledTimestamp',
//    'field_favicon_uri' => 'faviconUri',
//    'field_json_data' => 'extensions',
//    'field_last_update' => 'lastUpdate',
//    'field_logo_uri' => 'logoUri',
//    'field_php_self' => 'phpSelf',
//    'field_php_version' => 'phpVersion',
//    'field_public_path' => 'publicPath',
//    'field_server_address' => 'serverAddress',
//    'field_server_name' => 'serverName',
//    'field_site_identifier' => 'id',

    if ($this->data === NULL) {
      $this->data = array(
        'type' => array(array('target_id' => 'site_instance')),
      );
      $fields = array(
        'title' => $this->core->siteName(),
        'field_base_url' => $this->core->baseUrl(),
        'field_db_url_hash' => $this->core->databaseHash(),
        'field_enable_timestamp' => $this->core->enableTimestamp(),
        'field_favicon_uri' => $this->core->absoluteUri($this->core->favicon()),
        'field_json_data' => $this->core->jsonEncode($this->core->extensionList()),
        'field_last_update' => $this->core->requestTime(),
        'field_logo_uri' => $this->core->absoluteUri($this->core->logo()),
        'field_php_self' => $this->core->server('PHP_SELF'),
        'field_php_version' => phpversion(),
        'field_public_path' => $this->core->publicPath(),
        'field_server_address' => $this->core->server('SERVER_ADDR'),
        'field_server_name' => $this->core->server('SERVER_NAME'),
        'field_site_identifier' => $this->core->siteIdentifier(),
        'field_tag1_api_version' => $this->core->getApiVersion(),
      );
      foreach ($fields as $field_name => $value) {
        if ($value === NULL) {
          continue;
        }
        $this->data[$field_name] = array(array('value' => $value));
      }
    }
    return $this->data;
  }

  /**
   * Retrieves the error code.
   *
   * @return int
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Retrieves the error message.
   *
   * @return string
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * Indicates whether in debug mode.
   *
   * @return bool
   */
  public function inDebugMode() {
    if ($this->debugMode === NULL) {
      $this->debugMode = $this->core->inDebugMode();
    }
    return $this->debugMode;
  }

  /**
   * Indicates whether a heartbeat will be sent.
   *
   * @return bool
   */
  public function isEnabled() {
    // If enabled is not explicitly set, then determine if its enabled by
    if ($this->enabled === NULL) {
      $this->enabled = $this->config->get('enabled', TRUE);
    }
    return $this->enabled;
  }

  /**
   * Indicates whether the heartbeat is stale and should be sent again.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isStale() {
    if ($this->stale === NULL) {
      $this->stale = $this->core->requestTime() >= $this->nextTimestamp();
    }
    return $this->stale;
  }

  /**
   * Indicates the last time a heartbeat was sent.
   *
   * @return int
   *   The UNIX timestamp of when the heartbeat was last sent.
   */
  public function lastTimestamp() {
    return (int) $this->core->state()->get('tag1quo_heartbeat_timestamp', 0);
  }

  /**
   * Indicates the next time a heartbeat should be sent.
   *
   * @return int
   *   THe UNIX timestamp of when the heart should be sent next.
   */
  public function nextTimestamp() {
    // Use the last timestamp or the current request time, minus 10 minutes.
    $lastTimestamp = $this->lastTimestamp() ?: $this->core->requestTime() - static::FREQUENCY - 600;
    return $lastTimestamp + static::FREQUENCY;
  }

  /**
   * Retrieves the request options to be sent to the HTTP client.
   *
   * @return array
   *   An array of key/value HTTP client request options.
   */
  protected function requestOptions() {
    $options = array(
      'gzip' => TRUE,
      'headers' => array(
        'Accept' => 'application/vnd.quo.v' . $this->core->getApiVersion() . '+json',
      ),
    );
    if ($data = $this->getData()) {
      $options['json'] = $data;
    }
    if ($this->apiToken !== NULL) {
      $options['headers']['X-Drupal-Token-Auth'] = $this->apiToken;
    }
    if ($this->debugMode !== NULL) {
      $options['debug'] = $this->debugMode;
    }
    if ($this->useCurl !== NULL) {
      $options['curl'] = $this->useCurl;
    }
    return $options;
  }

  /**
   * Sends the Heartbeat to Tag1 Quo.
   **
   * @return \Drupal\tag1quo\Adapter\Http\JsonResponse
   *   A response from sending the data.
   */
  public function send() {
    // Immediately return if there is an error.
    $this->validate();
    if ($errorMessage = $this->getErrorMessage()) {
      return $this->errorResponse($errorMessage);
    }

    if (!$this->core->lockAcquire('tag1quo_send_heartbeat')) {
      return $this->errorResponse('Unable to acquire lock.');
    }

    $response = $this->core->httpClient()->post($this->core->getApiEndpoint(), $this->requestOptions());

    $success = $response->isSuccessful();
    if ($success) {
      $this->core->state()->set('tag1quo_heartbeat_timestamp', $this->core->requestTime());
      $this->core->logger()->info('Successfully sent data to Tag1 Quo (%url).', array('%url' => $this->core->getApiEndpoint()));
    }
    else {
      $this->core->logger()->info('Failed to send data to Tag1 Quo (%url).', array('%url' => $this->core->getApiEndpoint()));
    }

    if ($this->showMessage()) {
      if ($success) {
        $this->core->setMessage('Report sent to Tag1 Quo.');
      }
      else {
        $this->core->setMessage('Unable to send report to Tag1 Quo.', 'error');
      }
    }

    $this->core->lockRelease('tag1quo_send_heartbeat');

    return $response;
  }

  /**
   * Sets the API token used for request authentication with the server.
   *
   * @param string $apiToken
   *   The API token.
   *
   * @return static
   */
  public function setApiToken($apiToken = NULL) {
    $this->apiToken = $apiToken;
    return $this;
  }

  /**
   * Sets the data to send for the heartbeat.
   *
   * @param array $data
   *   The data to send.
   *
   * @return static
   */
  public function setData(array $data = array()) {
    $this->data = $data;
    return $this;
  }

  /**
   * Sets the debug mode.
   *
   * @param bool $debugMode
   *   The debug mode.
   *
   * @return static
   */
  public function setDebugMode($debugMode = NULL) {
    if ($debugMode !== NULL) {
      $debugMode = !!$debugMode;
    }
    $this->debugMode = $debugMode;
    return $this;
  }

  /**
   * Sets the error message.
   *
   * @param string $errorMessage
   *   The error message.
   *
   * @return static
   */
  public function setErrorMessage($errorMessage = NULL) {
    $this->errorMessage = (string) $errorMessage;
    return $this;
  }

  /**
   * Sets if heartbeat is enabled.
   *
   * @param bool $enabled
   *   The enabled state.
   *
   * @return static
   */
  public function setEnabled($enabled = NULL) {
    if ($enabled !== NULL) {
      $enabled = !!$enabled;
    }
    $this->enabled = $enabled;
    return $this;
  }

  /**
   * Sets the error code.
   *
   * @param int $code
   *   The error code.
   * @param string $message
   *   Optional. A custom message to use with error code. If not set, a default
   *   message that applies to the error code will be used.
   *
   * @return static
   */
  public function setError($code = 0, $message = NULL) {
    $this->error = $code;

    if ($code) {
      $args = array(
        '@title' => Core::TITLE,
        '!configuration_page' => $this->core->l($this->core->t('configuration page'), 'tag1quo.admin_settings'),
      );

      switch ($code) {
        case static::ERROR_DISABLED:
          $message = $message ?: $this->core->t('No data was sent to @title. Heartbeats are currently disabled. You can enable it on the !configuration_page.', $args);
          break;

        case static::ERROR_TOO_SOON:
          $message = $message ?: $this->core->t('No data was sent to @title. Not enough time has elapsed since the last heartbeat was sent.', $args);
          break;

        case static::ERROR_TOKEN_MISSING:
          $message = $message ?: $this->core->t('No data was sent to @title. An API Token is required. You must first configure the API Token on the !configuration_page.', $args);
          break;

        case static::ERROR_TOKEN_INVALID:
          $message = $message ?: $this->core->t('No data was sent to @title. The API Token is invalid. It must be lower-cased alphanumeric string that is 32 characters in length. To re-configure the API token, visit the !configuration_page.', $args);
          break;

        case static::ERROR_SERVER_TOKEN_INVALID:
          $message = $message ?: $this->core->t('Unable to authenticate with the @title server using the provided API Token. It must be lower-cased alphanumeric string that is 32 characters in length. To re-configure the API token, visit the !configuration_page.', $args);
          break;

        case static::ERROR_SERVER:
          $message = $message ?: $this->core->t('The @title server was unable to process the heartbeat that was sent. Check the logs for more information.', $args);
          break;

        case static::ERROR_UNKNOWN:
        default:
          $message = $message ?: $this->core->t('An unknown error occurred.');
      }
    }

    return $this->setErrorMessage($message);
  }

  /**
   * Sets whether to show a message when a heartbeat is sent.
   *
   * @param bool $showMessage
   *   Flag indicating whether to show messages or not.
   *
   * @return static
   */
  public function setShowMessage($showMessage = NULL) {
    if ($showMessage !== NULL) {
      $showMessage = !!$showMessage;
    }
    $this->showMessage = $showMessage;
    return $this;
  }

  /**
   * Sets whether heartbeat is stale.
   *
   * @param bool $stale
   *   TRUE or FALSE
   *
   * @return static
   */
  public function setStale($stale = NULL) {
    if ($stale !== NULL) {
      $stale = !!$stale;
    }
    $this->stale = $stale;
    return $this;
  }

  /**
   * Sets whether to use cURL.
   *
   * @param bool $useCurl
   *   TRUE or FALSE
   *
   * @return static
   */
  public function setUseCurl($useCurl = NULL) {
    if ($useCurl!== NULL) {
      $useCurl = !!$useCurl;
    }
    $this->useCurl = $useCurl;
    return $this;
  }

  /**
   * Indicates whether to show a message after a heartbeat was sent.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function showMessage() {
    if ($this->showMessage === NULL) {
      $this->showMessage = FALSE;
    }
    return $this->showMessage;
  }

  /**
   * Validates the Heartbeat.
   *
   * @param bool $againstServer
   *   Flag indicating whether to validate the API token against the server.
   *
   * @return static
   */
  public function validate($againstServer = FALSE) {
    if (!$this->isEnabled()) {
      $this->setError(static::ERROR_DISABLED);
    }
    elseif(!$this->isStale()) {
      $this->setError(static::ERROR_TOO_SOON);
    }
    elseif (empty($apiToken = $this->getApiToken())) {
      $this->setError(static::ERROR_TOKEN_MISSING);
    }
    elseif (strlen($apiToken) !== 32 || preg_match('/[^a-z0-9]/', $apiToken)) {
      $this->setError(static::ERROR_TOKEN_INVALID);
    }
    elseif ($againstServer && !$this->validateAgainstServer()) {
      $this->setError(static::ERROR_SERVER_TOKEN_INVALID);
    }
    if ($errorMessage = $this->getErrorMessage()) {
      $this->core->logger()->error($errorMessage);
      if ($this->showMessage()) {
        $this->core->setMessage($errorMessage, 'error');
      }
    }
    return $this;
  }

  /**
   * Validates the API token against the server.
   *
   * @return bool
   *   TRUE or FALSE
   */
  protected function validateAgainstServer() {
    // Retrieve the currently set API token.
    $apiToken = $this->getApiToken();

    // Set the JSON data.
    $this->setData(array('token' => $apiToken));

    // Temporarily remove the API token so it's not added as an auth provider.
    $this->setApiToken(NULL);

    // Sent validation request.
    $success = $this->core->httpClient()->post($this->core->getApiEndpoint(Core::API_ENDPOINT_VALIDATE_TOKEN), $this->requestOptions())->isSuccessful();

    // Restore the API token.
    $this->setApiToken($apiToken);

    return $success;
  }

}
