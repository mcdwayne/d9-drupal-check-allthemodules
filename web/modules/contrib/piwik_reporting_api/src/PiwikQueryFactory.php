<?php

namespace Drupal\piwik_reporting_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\piwik_reporting_api\Exception\MissingPiwikServerUrlException;
use GuzzleHttp\Client;
use Piwik\ReportingApi\QueryFactory;
use Piwik\ReportingApi\QueryInterface;

/**
 * Factory for Piwik query objects.
 *
 * This wraps the query factory from the Piwik Reporting API PHP library.
 *
 * @see \Piwik\ReportingApi\QueryFactory
 */
class PiwikQueryFactory implements PiwikQueryFactoryInterface {

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
   * The query factory from the Piwik Reporting API PHP library.
   *
   * @var \Piwik\ReportingApi\QueryFactoryInterface
   */
  protected $queryFactory;

  /**
   * Constructs a new PiwikQueryFactory.
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
   * @param \Piwik\ReportingApi\QueryInterface $query
   *   The query to which to apply the default parameters.
   */
  protected function setDefaultParameters(QueryInterface $query) {
    $piwik_config = $this->configFactory->get('piwik.settings');
    $piwik_reporting_api_config = $this->configFactory->get('piwik_reporting_api.settings');

    // The user authentication token.
    if ($token_auth = $piwik_reporting_api_config->get('token_auth')) {
      $query->setParameter('token_auth', $token_auth);
    }

    // The site ID.
    if ($site_id = $piwik_config->get('site_id')) {
      $query->setParameter('idSite', $site_id);
    }
  }

  /**
   * Generates and returns a new instance of the factory, with defaults applied.
   *
   * @return \Piwik\ReportingApi\QueryFactoryInterface
   *   The query factory.
   *
   * @throws \Drupal\piwik_reporting_api\Exception\MissingPiwikServerUrlException
   *   Thrown when the Piwik server URL is not configured.
   */
  protected function createFactoryInstance() {
    $piwik_config = $this->configFactory->get('piwik.settings');

    // Use the HTTPS connection if possible, with a fallback to the insecure
    // connection.
    $url = $piwik_config->get('url_https');
    if (empty($url)) {
      $url = $piwik_config->get('url_http');
    }

    // Log an error if the Piwik server URL is not configured.
    if (empty($url)) {
      $this->loggerFactory->get('piwik_reporting_api')->error('Piwik cannot be queried. The URL of the Piwik server is not configured.');
      throw new MissingPiwikServerUrlException();
    }

    // Log a warning if the communication with the Piwik server is insecure.
    if (parse_url($url)['scheme'] !== 'https') {
      $this->loggerFactory->get('piwik_reporting_api')->warning('The communication with the Piwik server is insecure. Make sure to use HTTPS for the Piwik server URL so that the user credentials are safely encrypted and cannot be abused by potential attackers.');
    }

    return new QueryFactory($url, $this->httpClient);
  }

}
