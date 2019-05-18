<?php

namespace Drupal\patchinfo_drupalorg;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class PatchinfoDrupalorgService contains commonly shared utilities.
 *
 * @package Drupal\patchinfo_drupalorg
 */
class PatchinfoDrupalorgService {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new PatchinfoDrupalorgService instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The HTTP client factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(CacheBackendInterface $cache, ClientFactory $http_client_factory, TimeInterface $time) {
    $this->cache = $cache;
    $this->httpClientFactory = $http_client_factory;
    $this->time = $time;
  }

  /**
   * Gets information about issue on drupal.org.
   *
   * @param string $issue_number
   *   Issue number on drupal.org.
   *
   * @return array
   *   Array with field information.
   */
  public function getIssue($issue_number) {
    $cid = 'drupalorgLookupIssue:' . $issue_number;
    $cache = $this->cache->get($cid);

    if ($cache) {
      return (array) $cache->data;
    }
    // Get url content from Guzzle.
    /* @var \GuzzleHttp\Client $http_client */
    $http_client = $this->httpClientFactory->fromOptions([
      'headers' => ['Content-Type' => ['application/json']],
      'timeout' => 10,
    ]);
    $service_url = 'https://www.drupal.org/api-d7/node.json?nid=' . $issue_number;
    try {
      $response = $http_client->request('GET', $service_url);
      $reponse_body = $response->getBody();
      $decoded = json_decode($reponse_body);
      if ($response->getStatusCode() !== 200) {
        drush_print('Error retrieving issue ' . $issue_number . ' , error:' . $response->getReasonPhrase());
        return [];
      }
      $composer_module_issue = reset($decoded->list);
      $this->cache->set($cid, $composer_module_issue, $this->time->getRequestTime() + 3700);
      return (array) $composer_module_issue;
    }
    catch (RequestException $e) {
      drush_print('Error retrieving issue ' . $issue_number . ' , error:' . $e->getMessage());
      return [];
    }
    catch (GuzzleException $e) {
      drush_print('Error retrieving issue ' . $issue_number . ' , error:' . $e->getMessage());
      return [];
    }
  }

}
