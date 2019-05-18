<?php

namespace Drupal\acquia_contenthub;

use Acquia\ContentHubClient\Settings;
use Drupal\Core\Config\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Responsible for connection management actions.
 *
 * @package Drupal\acquia_contenthub
 */
class ContentHubConnectionManager {

  /**
   * Default cloud filter prefix.
   *
   * @var string
   */
  const DEFAULT_FILTER = 'default_filter_';

  /**
   * Error code received when trying to create a webhook that already exists.
   *
   * @var integer
   */
  const WEBHOOK_ALREADY_EXISTS = 4010;

  /**
   * The Content Hub configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The Content Hub Client.
   *
   * @var \Acquia\ContentHubClient\ContentHubClient
   */
  protected $client;

  /**
   * The acquia_contenthub logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Content Hub settings.
   *
   * @var \Acquia\ContentHubClient\Settings
   */
  protected $settings;

  /**
   * ContentHubConnectionManager constructor.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The acquia_conetnthub.admin_settings config.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Acquia\ContentHubClient\Settings $settings
   *   The settings object constructed from Content Hub settings form.
   */
  public function __construct(Config $config, LoggerInterface $logger, Settings $settings) {
    $this->config = $config;
    $this->logger = $logger;
    $this->settings = $settings;
  }

  /**
   * Sets the client.
   *
   * It is acquired through ClientFactory:getClient() method.
   * However this can also be FALSE, therefore it is recommended to make sure
   * the client is bootstrapped before the connection manager is being used.
   *
   * @param \Acquia\ContentHubClient\ContentHubClient|false $client
   *   The Content Hub client if it's already been configured, FALSE otherwise.
   */
  public function setClient($client) {
    $this->client = $client;
  }

  /**
   * Registers a webhook if it has not been registered already.
   *
   * @param string $webhook_url
   *   The webhook url with to register. Provide the full route
   *   (/acquia-contenthub/webhook).
   *
   * @return array
   *   The response of the attempt.
   *
   * @throws \Exception
   */
  public function registerWebhook(string $webhook_url): array {
    $response = $this->client->addWebhook($webhook_url);
    if (isset($response['success']) && $response['success'] === FALSE) {
      if (isset($response['error']['code']) && $response['error']['code'] === self::WEBHOOK_ALREADY_EXISTS) {
        $response = $this->client->getWebHook($webhook_url);
      }
      else {
        $this->logger->error('Unable to create URL @url, Error @e_code: @e_message',
          [
            '@url' => $webhook_url,
            '@e_code' => $response['error']['code'],
            '@e_message' => $response['error']['message'],
          ]);
        return [];
      }
    }
    $this->addDefaultFilterToWebhook($response['uuid']);

    return $response;
  }

  /**
   * Adds default filter to a Webhook.
   *
   * @param string $webhook_uuid
   *   The webhook UUID.
   *
   * @throws \Exception
   */
  public function addDefaultFilterToWebhook(string $webhook_uuid): void {
    $filter_name = self::DEFAULT_FILTER . $this->client->getSettings()->getName();
    $filter = $this->createDefaultFilter($filter_name);
    $list = $this->client->listFiltersForWebhook($webhook_uuid);
    if ($filter['success'] === TRUE && isset($list['data']) && in_array($filter['uuid'], $list['data'], TRUE)) {
      // The default filter is already attached to the current webhook.
      return;
    }

    // Default Filter for the current client exists but is not attached to this
    // client's webhook.
    if (!isset($filter['uuid'])) {
      return;
    }

    $response = $this->client->addFilterToWebhook($filter['uuid'], $webhook_uuid);
    if (isset($response['success']) && $response['success'] === FALSE) {
      $this->logger->error('Could not add default filter (@d_filter) to webhook (@whuuid).',
        [
          '@d_filter' => $filter_name,
          '@webhook' => $webhook_uuid,
        ]);
      return;
    }

    $this->logger->notice('Added filter "@filter" (@uuid) to webhook @whuuid.', [
      '@filter' => $filter_name,
      '@uuid' => $filter['uuid'],
      '@whuuid' => $webhook_uuid,
    ]);
  }

  /**
   * Updates the specified webhook on Content Hub.
   *
   * @param string $webhook_url
   *   The webhook to update.
   *
   * @return array
   *   The response of the attempt.
   *
   * @throws \Exception
   */
  public function updateWebhook(string $webhook_url): array {
    if (!$this->webhookIsRegistered($this->settings->getWebhook('url'))) {
      return $this->registerWebhook($webhook_url);
    }

    if ($this->webhookIsRegistered($webhook_url)) {
      $this->logger->error('The webhook @webhook has already been registered!', ['@webhook' => $webhook_url]);
      return [];
    }

    $response = $this->handleResponse($this->client->updateWebhook($this->settings->getWebhook('uuid'), $webhook_url));
    if (!isset($response['success'])) {
      $this->logger->error('Unexpected error occurred during webhook update. Response: @resp', ['@resp' => print_r($response)]);
      return [];
    }

    if ($response['success'] === FALSE) {
      if (!isset($response['error']['message'])) {
        $this->logger->error('Unable to update URL %url, Unable to connect to Content Hub.');
        return [];
      }

      $this->logger->error('Unable to update URL %url, Error %error: %error_message.', [
        '%url' => $webhook_url,
        '%error' => $response['error']['code'],
        '%error_message' => $response['error']['message'],
      ]);
      return [];
    }

    $this->logger->notice('Webhook url @old has been successfully updated to @new',
      [
        '@old' => $this->settings->getWebhook('settings_url'),
        '@new' => $webhook_url,
      ]);

    return $response['data'] ?? $response;
  }

  /**
   * Unregisters the client.
   *
   * @throws \Exception
   */
  public function unregister(): void {
    // Make sure nothing has changed in our client since the setup of this
    // service, therefore use the settings from client instead.
    $this->settings = $this->client->getSettings();
    $webhook_uuid = $this->settings->getWebhook('uuid');
    if ($webhook_uuid) {
      $resp = $this->client->deleteWebhook($webhook_uuid);
      if ($resp instanceof ResponseInterface && $resp->getStatusCode() !== Response::HTTP_OK) {
        $this->logger->error('Could not unregister webhook: @e_message', ['@e_message' => $resp->getReasonPhrase()]);
        return;
      }
    }

    $client_name = $this->settings->getName();
    $default_filter = $this->client->getFilterByName(self::DEFAULT_FILTER . $client_name);
    if (isset($default_filter['uuid'])) {
      $this->client->deleteFilter($default_filter['uuid']);
    }

    $resp = $this->client->deleteClient();
    if ($resp instanceof ResponseInterface && $resp->getStatusCode() !== Response::HTTP_OK) {
      $this->logger->error('Could not delete client: @e_message', ['@e_message' => $resp->getReasonPhrase()]);
      return;
    }

    $this->logger->notice('Successfully unregistered client @client', ['@client' => $client_name]);
    $this->config->delete();
  }

  /**
   * Check if client successfully registered.
   *
   * Check client first if needed before any action.
   *
   * @return $this
   *   Returns itself for the sake of chainability.
   *
   * @throws \RuntimeException
   * @throws \Exception
   */
  public function checkClient(): self {
    if (is_null($this->client)) {
      throw new \RuntimeException('Client is not configured.');
    }

    $resp = $this->client->ping();
    if (!isset($resp['success']) || $resp['success'] === FALSE) {
      throw new \RuntimeException('Client could not reach Content Hub.');
    }

    return $this;
  }

  /**
   * Creates default filter for the site.
   *
   * @param string $filter_name
   *   The name of the filter.
   *
   * @return array
   *   The response of the attempt.
   *
   * @throws \Exception
   */
  protected function createDefaultFilter(string $filter_name) {
    $filter = $this->client->getFilterByName($filter_name);
    // Only create default filter if it does not exist yet for the current
    // client.
    if (empty($filter['uuid'])) {
      $site_origin = $this->client->getSettings()->getUuid();
      $filter_query = [
        'bool' => [
          'should' => [
            [
              'match' => [
                'data.attributes.channels.value.und' => $site_origin,
              ],
            ],
            [
              'match' => [
                'data.origin' => $site_origin,
              ],
            ],
          ],
        ],
      ];
      $filter = $this->client->putFilter($filter_query, $filter_name);
    }

    return $filter;
  }

  /**
   * Checks whether the webhook has already been registered.
   *
   * @param string $webhook_url
   *   The webhook's url.
   *
   * @return bool
   *   TRUE if the webhook is registered.
   *
   * @throws \Exception
   */
  protected function webhookIsRegistered(string $webhook_url): bool {
    $resp = $this->client->getWebHook($webhook_url);
    return !empty($resp);
  }

  /**
   * Handles incoming response.
   *
   * A response can either contain a json body which has the data,
   * or a reason phrase containing the error message. In some cases the latter
   * one can also come in an array structure.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   *
   * @return array
   *   The response decoded into array.
   */
  protected function handleResponse(ResponseInterface $response): array {
    $body = json_decode((string) $response->getBody(), TRUE);
    if (empty($body)) {
      return [
        'success' => FALSE,
        'error' => [
          'message' => $response->getReasonPhrase(),
        ],
      ];
    }

    return $body;
  }

}
