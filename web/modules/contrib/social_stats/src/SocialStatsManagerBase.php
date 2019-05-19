<?php
/**
 * Created by PhpStorm.
 * User: piyuesh23
 * Date: 10/08/14
 * Time: 5:57 PM
 */

namespace Drupal\social_stats;


class SocialStatsManagerBase {

  /**
   * @var string
   * Url which needs to be requested to fetch stats
   */
  protected $baseUrl;

  /**
   * @var string
   * Absolute Path for which the stats need to be fetched.
   */
  protected $path;

  /**
   * @var integer
   * Node id of the content being queried for stats
   */
  protected $nid;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $request;

  /**
   * @var array
   * Holds the response object obtained on requesting the status url
   */
  protected $response;

  /**
   * @var string
   * Request method POST or GET
   */
  private $method;

  /**
   * @var array
   * Additional information to be passed with the request
   */
  private $options;

  /**
   * Constructs a SocialStatsManager object.
   * @param string $baseUrl
   * @param string $path
   * @param string $nid
   * @param string $method
   * @param array $options
   */
  public function __construct($baseUrl = '', $path = '', $nid = '', $method = 'get', $options = array()){
    $this->baseUrl = $baseUrl;
    $this->path = $path;
    $this->nid = $nid;
    $this->method = $method;
    $this->options = $options;
  }

  /**
   * Query url builder
   */
  public function buildQueryUrl() {
    $this->requestUrl = $this->baseUrl . '?url=' . $this->path;
  }

  /**
   * Sends a GET request on the url
   */
  public function get() {
    $this->request = \Drupal::httpClient()->get($this->requestUrl, $this->options);
    try {
      $this->response = $this->request->json();
    }
    catch(RequestException $e) {
      \Drupal::logger('social_stats')->info('Problem updating data from Facebook for %node_path. Error: %err',
        array('%node_path' => $this->path, '%err' => $e));
      watchdog_exception('social_stats', $e);
    }
  }

  /**
   * Posts the data defined in $options at $url
   */
  public function post() {
    $this->request = Drupal::httpClient()->post($this->url, $this->options);
    try {
      $this->response = $this->request->json();
    }
    catch(RequestException $e) {
      watchdog_exception('social_stats', $e);
    }
  }

  /**
   * Processes the response & inserts in database
   */
  public function processResponse() {

  }

  /**
   * Executes functions step by step
   */
  public function execute() {
    $this->buildQueryUrl();
    if ($this->method === 'get') {
      $this->get();
    }
    elseif ($this->method === 'post') {
      $this->post();
    }

    $this->processResponse();
  }
} 