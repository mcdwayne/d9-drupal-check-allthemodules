<?php

namespace Drupal\acquia_contenthub_server_test\Client;

use Acquia\ContentHubClient\ContentHubClient;
use Acquia\ContentHubClient\Settings;
use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use Drupal\acquia_contenthub_test\MockDataProvider;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Mocks server responses.
 */
class ContentHubClientMock extends ContentHubClient {

  /**
   * Content Hub settings.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Test webhook values.
   *
   * @var array
   */
  protected $testWebhook = [
    'uuid' => '4e68da2e-a729-4c81-9c16-e4f8c05a11be',
    'url' => '',
  ];

  /**
   * {@inheritdoc}
   */
  public static function register(LoggerInterface $logger, EventDispatcherInterface $dispatcher, $name, $url, $api_key, $secret, $api_version = 'v1') {
    if ($url !== MockDataProvider::VALID_HOSTNAME) {
      throw new RequestException(
        "Could not get authorization from Content Hub to register client ${name}. Are your credentials inserted correctly?",
        new Request('POST', \Drupal::request()->getRequestUri())
      );
    }

    if ($api_key !== MockDataProvider::VALID_API_KEY) {
      self::generateErrorResponse("[4001] Not Found: Customer Key $api_key could not be found.", 401);
    }

    if ($secret !== MockDataProvider::VALID_SECRET) {
      self::generateErrorResponse('[4001] Signature for the message does not match expected signature for API key.', 401);
    }

    if ($name !== MockDataProvider::VALID_CLIENT_NAME) {
      self::generateErrorResponse('Name is already in use within subscription.', 4006);
    }

    $config = [
      'base_uri' => "$url/$api_version",
      'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => self::LIBRARYNAME . '/' . static::VERSION . ' ' . \GuzzleHttp\default_user_agent(),
      ],
    ];

    $key = new Key($api_key, $secret);
    $middleware = new HmacAuthMiddleware($key);
    $settings = new Settings($name, MockDataProvider::SETTINGS_UUID, $api_key, $secret, $url);

    return new ContentHubClientMock($config, $logger, $settings, $middleware, $dispatcher, $api_version);
  }

  /**
   * {@inheritdoc}
   */
  public function ping() {
    return [
      'success' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function addWebhook($webhook_url) {
    $this->testWebhook['url'] = $webhook_url;
    if ($webhook_url !== sprintf('%s/acquia-contenthub/webhook', MockDataProvider::VALID_WEBHOOK_URL)) {
      return [
        'success' => FALSE,
        'error' => [
          'code' => 4005,
          'message' => 'The provided URL did not respond with a valid authorization.',
        ],
        'request_id' => MockDataProvider::randomUuid(),
      ];
    }

    return [
      'client_name' => $this->options['name'],
      'client_uuid' => $this->options['uuid'],
      'disable_retries' => TRUE,
      'url' => $webhook_url,
      'uuid' => $this->testWebhook['uuid'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getWebHook($webhook_url) {
    if ($webhook_url === sprintf('%s/acquia-contenthub/webhook', MockDataProvider::ALREADY_REGISTERED_WEBHOOK)) {
      return [
        'client_name' => $this->options['name'],
        'client_uuid' => $this->options['uuid'],
        'disable_retries' => TRUE,
        'url' => $webhook_url,
        'uuid' => $this->testWebhook['uuid'],
      ];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function updateWebhook($uuid, $url) {
    return [
      'client_name' => $this->options['name'],
      'client_uuid' => $this->options['uuid'],
      'disable_retries' => TRUE,
      'url' => $url,
      'uuid' => $uuid,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWebhook($uuid) {
    return [
      'success' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteSettings() {
    $this->options = $this->getSettings()->toArray();
    return [
      'clients' => [
        [
          'name' => $this->options['name'],
          'uuid' => $this->options['uuid'],
        ],
      ],
      'success' => TRUE,
      'uuid' => MockDataProvider::randomUuid(),
      'webhooks' => [
        'client_name' => $this->options['name'],
        'client_uuid' => $this->options['uuid'],
        'disable_retries' => FALSE,
        'url' => $this->testWebhook['url'],
        'uuid' => $this->testWebhook['uuid'],
      ],
      'shared_secret' => 'kh32j32132143143276bjsdnfjdhuf3',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterByName($filter_name) {
    $filter = MockDataProvider::mockFilter();
    if ($filter !== $filter_name) {
      return [
        'success' => FALSE,
      ];
    }
    return [
      'uuid' => $filter['uuid'],
      'request_id' => MockDataProvider::randomUuid(),
      'success' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function listFiltersForWebhook($webhook_id) {
    $filter = MockDataProvider::mockFilter();
    return [
      'data' => [
        $filter['uuid'],
      ],
      'request_id' => MockDataProvider::randomUuid(),
      'success' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function putFilter($query, $name = '', $uuid = NULL, $metadata = []) {
    $filter = MockDataProvider::mockFilter();
    return [
      'request_id' => MockDataProvider::randomUuid(),
      'success' => TRUE,
      'uuid' => $filter['uuid'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteClient($client_uuid = NULL) {
    return [
      'success' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getClients() {
    $this->options = $this->getSettings()->toArray();
    return [
      [
        'name' => $this->options['name'],
        'uuid' => $this->options['uuid'],
      ],
    ];
  }

  /**
   * Generates an error response.
   *
   * @param string $message
   *   The error message.
   * @param int $status
   *   The status code of the exception.
   */
  protected static function generateErrorResponse(string $message, int $status = 0): void {
    $resp_body['error'] = [
      'message' => $message,
    ];

    if ($status !== 0) {
      $resp_body['error']['code'] = $status;
    }

    throw new RequestException(
      $message,
      new Request('POST', \Drupal::request()->getRequestUri()),
      new Response($status, [], json_encode($resp_body))
    );
  }

}
