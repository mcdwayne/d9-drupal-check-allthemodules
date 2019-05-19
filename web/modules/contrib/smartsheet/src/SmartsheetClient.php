<?php

namespace Drupal\smartsheet;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Url;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Component\Serialization\Json;

/**
 * An HTTP client used to communicate with Smartsheet API.
 *
 * @see https://smartsheet-platform.github.io/api-docs
 *   The Smartsheet API documentation.
 */
class SmartsheetClient implements SmartsheetClientInterface {

  use StringTranslationTrait;

  /**
   * The base URL of the Smartsheet API.
   */
  const BASE_URL = 'https://api.smartsheet.com/2.0';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The Smartsheet config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new SmartsheetClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger for this channel.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    LoggerChannelFactory $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('smartsheet.config');
    $this->messenger = $messenger;
    $this->logger = $logger_factory->get('smartsheet');
  }

  /**
   * {@inheritdoc}
   */
  public function get($path, array $options = []) {
    return $this->request('GET', $path, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function post($path, array $data = [], array $options = []) {
    $options['json'] = $data;

    return $this->request('POST', $path, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function put($path, array $data = [], array $options = []) {
    $options['json'] = $data;

    return $this->request('PUT', $path, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($path, array $options = []) {
    return $this->request('DELETE', $path, $options);
  }

  /**
   * Performs an HTTP request to the Smartsheet API.
   *
   * @param string $method
   *   The method of the request, one of 'GET', 'POST', 'PUT' and 'DELETE'.
   * @param string $path
   *   The path of the request. Must begin with a /.
   * @param array $options
   *   (optional) An array of options for the request.
   *
   * @return array|false
   *   An array of metadata and data whose structure depends on the method, or
   *   FALSE if an error occurred.
   */
  protected function request($method, $path, array $options = []) {
    $access_token = $this->config->get('access_token');

    if (empty($access_token)) {
      $this->messenger->addMessage($this->t('The Smartsheet access token is not set, please set it <a href="@url">here</a>.', [
        '@url' => Url::fromRoute('smartsheet.config')->toString(),
      ]), MessengerInterface::TYPE_ERROR);

      return FALSE;
    }

    $options['headers']['Authorization'] = "Bearer $access_token";

    try {
      $response = $this->httpClient->request($method, self::BASE_URL . $path, $options);
    }
    catch (GuzzleException $exception) {
      $this->logger->error($exception->getMessage());

      $this->messenger->addMessage($this->t('An error occurred during the Smartsheet request. Please check the log <a href="@url">here</a>.', [
        '@url' => Url::fromRoute('dblog.overview')->toString(),
      ]), MessengerInterface::TYPE_ERROR);

      return FALSE;
    }

    return Json::decode($response->getBody());
  }

}
