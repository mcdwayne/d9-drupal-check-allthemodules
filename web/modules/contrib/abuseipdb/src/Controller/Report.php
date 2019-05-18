<?php

namespace Drupal\abuseipdb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\abuseipdb\Controller\Request;
use Drupal\ban\BanIpManager;

/**
 * Handles the reporting of IP addresses.
 */
class Report extends ControllerBase {

  protected $apiKey;
  protected $banManager;
  protected $categories;
  protected $cidr;
  protected $comment = '';
  protected $days = 30;
  protected $ip;
  protected $request;
  protected $response = NULL;

  /**
   * Initialize values which will be passed to a report request.
   *
   * @param array $options
   *   Report parameters array to set the class properties.
   */
  public function __construct(array $options = []) {
    foreach ($options as $key => $value) {
      $this->{$key} = $value;
    }

    // Set the REST API request controller.
    $this->request = new Request();

    // Set API key.
    $api_key = \Drupal::config('abuseipdb.settings')->get('abuseipdb.api_key');
    $this->setApiKey($api_key);

    // Remove categories which are unused.
    if ($this->categories) {
      $this->removeEmptyCategories($this->categories);
    }
  }

  /**
   * Ban current IP address.
   */
  public function ban() {
    $this->checkBanManager();
    $this->banManager->banIp($this->ip);
  }

  /**
   * Check if current IP is banned.
   */
  public function isBanned() {
    $this->checkBanManager();
    return $this->banManager->isBanned($this->ip);
  }

  /**
   * Check if Ban Manager exists.
   */
  protected function checkBanManager() {
    if (!$this->banManager) {
      $this->setBanManager();
    }
  }

  /**
   * Initialize the Drupal Module BanIPManager class.
   */
  protected function setBanManager() {
    $connection = \Drupal::service('database');
    $this->banManager = new BanIpManager($connection);
  }

  /**
   * Check the current IP address.
   */
  public function check() {
    $this->response = $this->request->check($this->apiKey, $this->ip, $this->days);
  }

  /**
   * Report the current IP address.
   */
  public function report() {
    $this->response = $this->request->report($this->apiKey, $this->ip, $this->categories, $this->comment);
  }

  /**
   * Check if the current IP is abusive based on AbuseIPDB reports.
   *
   * @return bool
   *   True if the IP is abusive and False if the IP is not.
   */
  public function isAbusive() {
    $body = $this->getResponseBody();
    $body_array = json_decode($body);
    if (!empty($body_array)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Return the AbuseIPDB Key.
   *
   * @return string
   *   Return the key.
   */
  public function getApiKey() {
    return $this->apiKey;
  }

  /**
   * Set the current AbuseIPDB Key.
   */
  public function setApiKey(string $api_key) {
    $this->apiKey = $api_key;
  }

  /**
   * Get the available categories of abuse.
   *
   * @return array
   *   List of integers which correspond to the abuse categories.
   */
  public function getCategories() {
    return $this->categories;
  }

  /**
   * Utility function to remove categories before reporting to AbuseIPDB.
   */
  public function removeEmptyCategories(array &$categories = []) {
    rsort($categories);
    while (count($categories) > 0 && $categories[count($categories) - 1] == 0) {
      array_pop($categories);
    }
  }

  /**
   * Sets the categories for this report.
   */
  public function setCategories(array $categories) {
    $this->removeEmptyCategories($categories);
    $this->categories = $categories;
  }

  /**
   * Return the report comment.
   *
   * @return string
   *   The comment.
   */
  public function getComment() {
    return $this->comment;
  }

  /**
   * Set the report comment.
   */
  public function setComment(string $comment) {
    $this->comment = $comment;
  }

  /**
   * Get the number of days back to query for abuse reports.
   *
   * @return int
   *   The number of days.
   */
  public function getDays() {
    return $this->days;
  }

  /**
   * Set the number of days back to query for abuse reports.
   */
  public function setDays(int $days) {
    $this->days = $days;
  }

  /**
   * Get the report IP address.
   *
   * @return string
   *   The IP address.
   */
  public function getIp() {
    return $this->ip;
  }

  /**
   * Set the report IP address.
   */
  public function setIp(string $ip) {
    $this->ip = $ip;
  }

  /**
   * Return the response Body from a Report request.
   *
   * @return string
   *   The response body from AbuseIPDB API.
   */
  public function getResponseBody() {
    return ($this->response) ? $this->response->getBody() : FALSE;
  }

  /**
   * Return the HTTP Status Code from a Report Request.
   *
   * @return int
   *   The HTTP response code from AbuseIPDB API.
   */
  public function getResponseStatusCode() {
    return ($this->response) ? $this->response->getStatusCode() : FALSE;
  }

}
