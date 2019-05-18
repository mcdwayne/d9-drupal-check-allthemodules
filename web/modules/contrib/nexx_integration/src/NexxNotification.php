<?php

namespace Drupal\nexx_integration;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class NexxNotification.
 *
 * @package Drupal\nexx_integration
 */
class NexxNotification implements NexxNotificationInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Notify nexxOMNIA video CMS.
   *
   * Notify when channel or actor terms have been updated,
   * or when a video has been created.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The config factory service.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    Client $http_client,
    TranslationInterface $translation
  ) {
    $this->config = $config_factory->get('nexx_integration.settings');
    $this->logger = $logger_factory->get('nexx_integration');
    $this->httpClient = $http_client;
    $this->stringTranslation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function insert($streamtype, $reference_number, $values) {
    if ($streamtype === 'video') {
      throw new \InvalidArgumentException(sprintf('Streamtype cannot be "%s" in insert operation.', $streamtype));
    }
    $response = $this->notificateNexx($streamtype, $reference_number, 'insert', $values);
    if ($response) {
      $this->logger->info("insert @type. Reference number: @reference, values: @values", [
        '@type' => $streamtype,
        '@reference' => $reference_number,
        '@values' => print_r($values, TRUE),
      ]
      );
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function update($streamtype, $reference_number, $values) {
    $response = $this->notificateNexx($streamtype, $reference_number, 'update', $values);
    if ($response) {
      $this->logger->info("update @type. Reference number: @reference, values: @values", [
        '@type' => $streamtype,
        '@reference' => $reference_number,
        '@values' => print_r($values, TRUE),
      ]
      );
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($streamtype, $reference_number, array $values) {
    if ($streamtype === 'video') {
      throw new \InvalidArgumentException(sprintf('Streamtype cannot be "%s" in delete operation.', $streamtype));
    }
    $response = $this->notificateNexx($streamtype, $reference_number, 'delete', $values);
    if ($response) {
      $this->logger->info("delete @type. Reference number: @reference", [
        '@type' => $streamtype,
        '@reference' => $reference_number,
      ]
      );
    }

    return $response;
  }

  /**
   * Send a crud notification to nexx.
   *
   * @param string $streamtype
   *   The data type to update. Possible values are:
   *   - "actor"
   *   - "channel"
   *   - "tag"
   *   - "video".
   * @param string $reference_number
   *   Reference id. In case of streamtype video, this is the nexx ID in all
   *   other cases, this is the corresponding drupal id.
   * @param string $command
   *   CRUD operation. Possible values are:
   *   - "insert"
   *   - "update"
   *   - "delete".
   * @param string[] $values
   *   The values to be set.
   *
   * @return string[]|false
   *   Decoded response.
   */
  protected function notificateNexx(
    $streamtype,
    $reference_number,
    $command,
    array $values = []
  ) {
    $api_authkey = $this->config->get('nexx_api_authkey');
    $omnia_id = $this->config->get('omnia_id');

    $api_url = $this->config->get('nexx_api_url') . 'v3/' . $omnia_id . '/manage/' . $streamtype . '/';
    if ($command == 'insert' && $streamtype == 'persons') {
      $api_url .= 'fromdata/';
    }
    elseif ($command == 'insert') {
      $api_url .= 'add/';
    }
    elseif ($command == 'update') {
      $api_url .= $values['item'] . '/update/';
    }
    elseif ($command == 'delete') {
      $api_url .= $values['item'] . '/remove/';
    }

    if ($api_url == '' || $api_authkey == '' || $omnia_id == '') {
      $this->logger->error("Missing configuration for API Url and/or Installation Code (API Key) and/or Omnia ID.");
      drupal_set_message($this->t("Item wasn't exported to Nexx due to missing configuration for API Url and/or Installation Code (API Key) and/or Omnia ID."), 'error');

      return FALSE;
    }

    $response_data = [];
    $headers = [
      'X-Request-Token' => md5($streamtype . $this->config->get('omnia_id') . $this->config->get('api_secret')),
      'X-Request-CID' => $api_authkey,
    ];

    if (isset($values)) {
      $values['refnr'] = $reference_number;
    }

    try {
      $response = $this->httpClient->request('POST', $api_url,
        [
          'headers' => $headers,
          'form_params' => $values,
        ]
      );
      $response_data = Json::decode($response->getBody()->getContents());

      if ($response_data['result']['message'] !== 'ok') {
        $this->logger->error("Omnia request failed: @error", [
          '@error' => $response_data['info'],
        ]
        );
      }
      else {
        $this->logger->info("Successful notification. Streamtype '@streamtype', command '@command', refnr '@refnr', values '@values'", [
          '@streamtype' => $streamtype,
          '@command' => $command,
          '@refnr' => $reference_number,
          '@values' => print_r($values, TRUE),
        ]
        );
      }
    }
    catch (RequestException $e) {
      $this->logger->error("HTTP request failed: @error", [
        '@error' => $e->getMessage(),
      ]
      );
    }
    return $response_data;
  }

}
