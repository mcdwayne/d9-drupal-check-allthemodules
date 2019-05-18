<?php

namespace Drupal\acquia_contenthub_server_test\Client;

use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\Component\Uuid\Uuid;

/**
 * Mocks the client factory service.
 */
class ClientFactoryMock extends ClientFactory {

  /**
   * Override original, and replace Conent Hub client with mock.
   */
  public function registerClient(string $name, string $url, string $api_key, string $secret, string $api_version = 'v1') {
    return ContentHubClientMock::register($this->loggerFactory->get('acquia_contenthub'), $this->dispatcher, $name, $url, $api_key, $secret);
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (isset($this->client)) {
      return $this->client;
    }

    if (!$this->settings
      || !Uuid::isValid($this->settings->getUuid())
      || empty($this->settings->getName())
      || empty($this->settings->getUrl())
      || empty($this->settings->getApiKey())
      || empty($this->settings->getSecretKey())
    ) {
      return FALSE;
    }

    // Override configuration.
    $config = [
      'base_url' => $this->settings->getUrl(),
      'client-user-agent' => $this->getClientUserAgent(),
    ];

    $this->client = new ContentHubClientMock(
      $config,
      $this->loggerFactory->get('acquia_contenthub'),
      $this->settings,
      $this->settings->getMiddleware(),
      $this->dispatcher
    );

    return $this->client;
  }

}
