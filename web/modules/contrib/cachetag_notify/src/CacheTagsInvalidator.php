<?php

/**
 * @file
 * Contains \Drupal\cachetag_notify\CacheTagsInvalidator.
 */

namespace Drupal\cachetag_notify;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Cache tags invalidator implementation that notifies a thirdparty.
 */
class CacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * Drupal Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a CacheTagsInvalidator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal Config.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->config = $config_factory->get('cachetag_notify.settings');
    $this->httpClient = $http_client;
  }

  /**
     * {@inheritdoc}
     */
  public function invalidateTags(array $tags) {
    $endpoint_url = $this->config->get('endpoint');
    if (empty($endpoint_url)) {
      \Drupal::logger('CacheTag Notify')->log('error', 'No endpoint set');
      return;
    }

    try {
      $this->httpClient->post($endpoint_url, [ 'body' => json_encode($tags) ]);
    }
    catch (ClientException $e) {
      watchdog_exception('CacheTag Notify', $e);
    }
    catch (ServerException $e) {
      watchdog_exception('CacheTag Notify', $e);
    }
    catch (ConnectException $e) {
      watchdog_exception('CacheTag Notify', $e);
    }
    catch (Exception $e) {
      watchdog_exception('CacheTag Notify', $e);
    }
  }

}

