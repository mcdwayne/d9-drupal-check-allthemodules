<?php

namespace Drupal\optit\Optit;

class RESTclient {
  private $username;
  private $password;
  private $apiEndpoint;
  /** @var \GuzzleHttp\Client client */
  private $client;

  public function __construct($username, $password, $apiEndpoint) {
    $this->username = $username;
    $this->password = $password;
    $this->apiEndpoint = $apiEndpoint;
    $this->client = \Drupal::httpClient();
  }

  public function get($route, $urlParams = NULL, $postParams = NULL, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'GET', $urlParams, $postParams, $format);
  }

  public function post($route, $urlParams = NULL, $postParams = NULL, $options = [], $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'POST', $urlParams, $postParams, $format, $options);
  }

  public function put($route, $urlParams = NULL, $postParams = NULL, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'PUT', $urlParams, $postParams, $format);
  }

  public function delete($route, $urlParams = NULL, $postParams = NULL, $format = 'json') {
    return $this->drupalHTTPNightmare($route, 'DELETE', $urlParams, $postParams, $format);
  }


  private function drupalHTTPNightmare($route, $method = 'GET', $urlParams = NULL, $postParams = NULL, $format = 'json', $options = []) {
    $options = [];

    // Prepare authentication
    $options['auth'] = [$this->username, $this->password];
//    var_dump($options); die();

    // Prepare URL
    $url = "http://{$this->apiEndpoint}/{$route}.{$format}";
    if ($urlParams) {
      $url .= "?" . $this->mergeParams($urlParams);
    }

    // Prepare POST params
    if ($postParams) {
      $options['form_params'] = $postParams;
    }

    $res = $this->client->request($method, $url, $options);

    if ($res->getStatusCode() == 200) {
      return $this->decodeData($res->getBody(), $format);
    }
    else {
      return $this->handleError($res);
    }
  }

  private function mergeParams($params) {
    $param = [];
    foreach ($params as $key => $value) {
      $param[] = $key . '=' . urlencode($value);
    }
    return implode('&', $param);
  }

  private function decodeData($data, $format) {
    switch ($format) {
      case "json":
        // Due to fact that some callbacks return broken json documents, i need to handle situation where response is 200, but
        // details are broken.
        $decoded = json_decode($data, TRUE);
        if ($decoded === NULL) {
          return TRUE;
        }
        return $decoded;
      case "xml":
        // @todo: This absolutely fails! Use D8 functions to parse XML.
        return FALSE;
        break;
    }
    return TRUE;
  }

  private function handleError($response) {
    return FALSE;
  }
}
