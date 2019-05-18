<?php

namespace Drupal\abuseipdb\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;

/**
 * Handles sending and receiving requests to the AbuseIPDB API.
 */
class Request extends ControllerBase {

  private $client;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->client = new Client([
      'base_uri' => 'https://www.abuseipdb.com/',
    ]);
  }

  /**
   * Delivers a report to AbuseIPDB API.
   *
   * @param string $api_key
   *   The API key for the AbuseIPDB account.
   * @param string $ip
   *   The IP which is being checked or reported.
   * @param array $categories
   *   Categories for a report. Will be a list of integers.
   * @param string $comment
   *   Optional comment for report.
   *
   * @return Guzzle
   *   Guzzle class object.
   */
  public function report(string $api_key, string $ip, array $categories, string $comment = '') {
    $query = [];
    $query['key'] = $api_key;
    $query['category'] = implode(',', $categories);
    $query['comment'] = $comment;
    $query['ip'] = $ip;

    $res = $this->client->request('POST', 'report/json', ['query' => $query, 'http_errors' => FALSE]);

    return $res;
  }

  /**
   * Query the AbuseIPDB API for IP reports.
   *
   * @param string $api_key
   *   The API key for the AbuseIPDB account.
   * @param string $ip
   *   The IP which is being checked or reported.
   * @param int $days
   *   The number of days prior to return any reports of abuse.
   *
   * @return Guzzle
   *   Response property after request has been executed.
   */
  public function check(string $api_key, string $ip, int $days = NULL) {
    $query = [];
    $query['api_key'] = $api_key;

    $res = $this->client->request('GET', 'check/' . $ip . '/json', ['query' => $query, 'http_errors' => FALSE]);

    return $res;
  }

}
