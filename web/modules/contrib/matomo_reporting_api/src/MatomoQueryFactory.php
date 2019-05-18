<?php

namespace Drupal\matomo_reporting_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\matomo_reporting_api\Exception\MissingMatomoServerUrlException;
use GuzzleHttp\Client;
use Matomo\ReportingApi\QueryFactory;
use Matomo\ReportingApi\QueryInterface;

/**
 * Factory for Matomo query objects.
 *
 * This wraps the query factory from the Matomo Reporting API PHP library.
 *
 * @see \Matomo\ReportingApi\QueryFactory
 */
class MatomoQueryFactory implements MatomoQueryFactoryInterface {

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The query factory from the Matomo Reporting API PHP library.
   *
   * @var \Matomo\ReportingApi\QueryFactoryInterface
   */
  protected $queryFactory;

  /**
   * Constructs a new MatomoQueryFactory.
   *
   * @param \GuzzleHttp\Client $httpClient
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(Client $httpClient, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($method) {
    $factory = $this->getQueryFactory();
    $query = $factory->getQuery($method);
    $this->setDefaultParameters($query);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryFactory() {
    if (empty($this->queryFactory)) {
      $this->queryFactory = $this->createFactoryInstance();
    }

    return $this->queryFactory;
  }

  /**
   * Retrieves default parameters from config and applies them to the query.
   *
   * @param \Matomo\ReportingApi\QueryInterface $query
   *   The query to which to apply the default parameters.
   */
  protected function setDefaultParameters(QueryInterface $query) {
    $matomo_config = $this->configFactory->get('matomo.settings');
    $matomo_reporting_api_config = $this->configFactory->get('matomo_reporting_api.settings');

    // The user authentication token.
    if ($token_auth = $matomo_reporting_api_config->get('token_auth')) {
      $query->setParameter('token_auth', $token_auth);
    }

    // The site ID.
    if ($site_id = $matomo_config->get('site_id')) {
      $query->setParameter('idSite', $site_id);
    }
  }

  /**
   * Generates and returns a new instance of the factory, with defaults applied.
   *
   * @return \Matomo\ReportingApi\QueryFactoryInterface
   *   The query factory.
   *
   * @throws \Drupal\matomo_reporting_api\Exception\MissingMatomoServerUrlException
   *   Thrown when the Matomo server URL is not configured.
   */
  protected function createFactoryInstance() {
    $matomo_config = $this->configFactory->get('matomo.settings');

    // Use the HTTPS connection if possible, with a fallback to the insecure
    // connection.
    $url = $matomo_config->get('url_https');
    if (empty($url)) {
      $url = $matomo_config->get('url_http');
    }

    // Log an error if the Matomo server URL is not configured.
    if (empty($url)) {
      $this->loggerFactory->get('matomo_reporting_api')->error('Matomo cannot be queried. The URL of the Matomo server is not configured.');
      throw new MissingMatomoServerUrlException();
    }

    // Log a warning if the communication with the Matomo server is insecure.
    if (parse_url($url)['scheme'] !== 'https') {
      $this->loggerFactory->get('matomo_reporting_api')->warning('The communication with the Matomo server is insecure. Make sure to use HTTPS for the Matomo server URL so that the user credentials are safely encrypted and cannot be abused by potential attackers.');
    }

    return new QueryFactory($url, $this->httpClient);
  }

}
