<?php

namespace Drupal\setka_editor;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;

/**
 * Setka Editor API service.
 */
class SetkaEditorApi {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  // @todo Move it to configuration, like "stage/live mode".
  const SETKA_API_CURRENT_BUILD_URL = 'https://editor.setka.io/api/v1/custom/builds/current';
  const SETKA_API_PUSH_SYSTEM_INFO = 'https://editor.setka.io/api/v1/drupal/current_theme.json';

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Setka Editor license key.
   *
   * @var string
   */
  protected $licenseKey;

  /**
   * Drupal messenger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal logger channel interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(Client $http_client, MessengerInterface $messenger, ConfigFactory $configFactory) {
    $config = $configFactory->get('setka_editor.settings');
    $this->messenger = $messenger;
    $this->logger = $this->getLogger('setka_editor');
    $this->licenseKey = $config->get('setka_license_key');
    $this->httpClient = $http_client;
  }

  /**
   * Returns current setka editor build data.
   *
   * @param string $licenseKey
   *   License key.
   *
   * @return bool|mixed
   *   Current setka editor build data or FALSE on error.
   */
  public function getCurrentBuild($licenseKey = NULL) {
    $queryLicenseKey = $licenseKey ?? $this->licenseKey;
    try {
      $uri = new Uri(self::SETKA_API_CURRENT_BUILD_URL);
      $uri = Uri::withQueryValue($uri, 'token', $queryLicenseKey);
      $response = $this->httpClient->request('GET', $uri);
      if ($response->getStatusCode() == 200) {
        $responseContent = $response->getBody()->getContents();
        if (!empty($responseContent)) {
          return Json::decode($responseContent);
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->error('Setka Editor API error on Get Current Build: @error', ['@error' => $e->getMessage()]);
      if ($e->getCode() == 401) {
        $this->messenger->addError($this->t('Invalid license key.'));
      }
      else {
        $response = $e->getResponse();
        $this->messenger->addError($response->getStatusCode() . ': ' . $response->getReasonPhrase());
      }
    }
    return FALSE;
  }

  /**
   * Pushes information about plugin, site and CMS to Style Manager.
   *
   * @param string $licenseKey
   *   Setka Editor license key.
   */
  public function pushSystemInfo($licenseKey = NULL) {
    global $base_url;
    $queryLicenseKey = $licenseKey ?? $this->licenseKey;
    $moduleInfo = system_get_info('module', 'setka_editor');
    $appVersion = \Drupal::VERSION;
    $pluginVersion = $moduleInfo['version'];
    $pluginVersion = empty($pluginVersion) ? '8.x-1.0' : $pluginVersion;
    $domain = $base_url;
    try {
      $uri = new Uri(self::SETKA_API_PUSH_SYSTEM_INFO);
      $uri = Uri::withQueryValue($uri, 'token', $queryLicenseKey);
      $uri = Uri::withQueryValue($uri, 'app_version', $appVersion);
      $uri = Uri::withQueryValue($uri, 'plugin_version', $pluginVersion);
      $uri = Uri::withQueryValue($uri, 'domain', $domain);
      $this->httpClient->request('POST', $uri);
    }
    catch (RequestException $e) {
      $this->logger->error('Setka Editor API error on System Info Push: @error', ['@error' => $e->getMessage()]);
    }
  }

}
