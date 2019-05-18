<?php

namespace Drupal\ckan_connect\Parser;

use Drupal\ckan_connect\Client\CkanClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Provides a CKAN resource URL parser.
 */
class CkanResourceUrlParser implements CkanResourceUrlParserInterface {

  /**
   * Regex to check whether a CKAN resource path is valid.
   */
  const CKAN_RESOURCE_PATH_REGEX = '/^\/dataset\/.*\/resource\/[^\/]+$/';

  /**
   * The CKAN client.
   *
   * @var \Drupal\ckan_connect\Client\CkanClientInterface
   */
  protected $ckanClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new CkanResourceUrlParser.
   *
   * @param \Drupal\ckan_connect\Client\CkanClientInterface $ckan_client
   *   The CKAN client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(CkanClientInterface $ckan_client, LoggerInterface $logger) {
    $this->ckanClient = $ckan_client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($url) {
    $options = parse_url($url);
    $path = !empty($options['path']) ? $options['path'] : '';

    if (!preg_match(self::CKAN_RESOURCE_PATH_REGEX, $path)) {
      return FALSE;
    }

    // Expected format /dataset/PACKAGE_ID/resource/RESOURCE_ID.
    $parts = explode('/', $options['path'], 5);
    $options = [
      'package_id' => $parts[2],
      'resource_id' => $parts[4],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageId($url) {
    $options = $this->parse($url);
    return !empty($options['package_id']) ? $options['package_id'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceId($url) {
    $options = $this->parse($url);
    return !empty($options['resource_id']) ? $options['resource_id'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceViewId($url) {
    $view_id = '';

    try {
      $query = ['id' => $this->getResourceId($url)];
      $response = $this->ckanClient->get('action/resource_view_list', $query);

      if (!empty($response->result) && is_array($response->result)) {
        $result = reset($response->result);

        if (!empty($result->id)) {
          $view_id = $result->id;
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->error('Error getting resource view ID. @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
    }

    return $view_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceViewUrl($url) {
    $view_url = '';
    $view_id = $this->getResourceViewId($url);

    if ($view_id) {
      $view_url = $url . '/view/' . $view_id;
    }

    return $view_url;
  }

}
