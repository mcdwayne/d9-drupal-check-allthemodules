<?php

namespace Drupal\clever_reach\Component\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\Infrastructure\Utility\HttpResponse;

/**
 * HTTP client implementation.
 *
 * @see \CleverReach\Infrastructure\Interfaces\Required\HttpClient
 */
class HttpClientService extends HttpClient {
  /**
   * Curl resource.
   *
   * @var null
   */
  private $curlSession;

  /**
   * Create and send request.
   *
   * @param string $method
   *   Request method.
   * @param string $url
   *   Request url.
   * @param array|null $headers
   *   Request headers.
   * @param string $body
   *   In JSON format.
   *
   * @return \CleverReach\Infrastructure\Utility\HttpResponse
   *   Http response object that contains response information such
   *   as headers, body and status.
   *
   * @throws HttpCommunicationException
   *   Only in situation when there is no connection, no response,
   *   throw this exception.
   */
  public function sendHttpRequest($method, $url, $headers = [], $body = '') {
    $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
    $this->setCurlSessionOptionsForSynchronousRequest();
    return $this->executeAndReturnResponseForSynchronousRequest($url);
  }

  /**
   * Create and send request asynchronously.
   *
   * @param string $method
   *   Request method.
   * @param string $url
   *   Request url.
   * @param array|null $headers
   *   Request headers.
   * @param string $body
   *   In JSON format.
   *
   * @return \CleverReach\Infrastructure\Utility\HttpResponse
   *   Http response object that contains response information such
   *   as headers, body and status.
   */
  public function sendHttpRequestAsync($method, $url, $headers = [], $body = '') {
    $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
    $this->setCurlSessionOptionsForAsynchronousRequest();
    return curl_exec($this->curlSession);
  }

  /**
   * Sets curl session parts.
   *
   * @param string $method
   *   Request method.
   * @param string $url
   *   Request url.
   * @param array $headers
   *   Request headers.
   * @param string $body
   *   In JSON format.
   */
  private function setCurlSessionAndCommonRequestParts($method, $url, array $headers, $body) {
    $this->initializeCurlSession();
    $this->setCurlSessionOptionsBasedOnMethod($method);
    $this->setCurlSessionUrlHeadersAndBody($method, $url, $headers, $body);
    $this->setCommonOptionsForCurlSession();
  }

  /**
   * Initializes curl session.
   */
  private function initializeCurlSession() {
    $this->curlSession = curl_init();
  }

  /**
   * Sets curl session options base od provided method.
   *
   * @param string $method
   *   Request method.
   */
  private function setCurlSessionOptionsBasedOnMethod($method) {
    if ($method === 'DELETE') {
      curl_setopt($this->curlSession, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    if ($method === 'POST') {
      curl_setopt($this->curlSession, CURLOPT_POST, TRUE);
    }
    if ($method === 'PUT') {
      curl_setopt($this->curlSession, CURLOPT_CUSTOMREQUEST, 'PUT');
    }
  }

  /**
   * Create and send request asynchronously.
   *
   * @param string $method
   *   Request method.
   * @param string $url
   *   Request url.
   * @param array $headers
   *   Request headers.
   * @param string $body
   *   In JSON format.
   */
  private function setCurlSessionUrlHeadersAndBody($method, $url, array $headers, $body) {
    curl_setopt($this->curlSession, CURLOPT_URL, $url);
    curl_setopt($this->curlSession, CURLOPT_HTTPHEADER, $headers);
    if ($method === 'POST') {
      curl_setopt($this->curlSession, CURLOPT_POSTFIELDS, $body);
    }
  }

  /**
   * Sets common options for curl session using curl_setopt function.
   */
  private function setCommonOptionsForCurlSession() {
    curl_setopt($this->curlSession, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($this->curlSession, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($this->curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($this->curlSession, CURLOPT_SSL_VERIFYHOST, FALSE);

    // Set default user agent, because for some systems if user agent is
    // missing, request will not work.
    curl_setopt(
        $this->curlSession,
        CURLOPT_USERAGENT,
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36'
    );
  }

  /**
   * Sets curl session options when sync request is used.
   */
  private function setCurlSessionOptionsForSynchronousRequest() {
    curl_setopt($this->curlSession, CURLOPT_HEADER, TRUE);
  }

  /**
   * Sets async request options.
   */
  private function setCurlSessionOptionsForAsynchronousRequest() {
    // Always ensure the connection is fresh.
    curl_setopt($this->curlSession, CURLOPT_FRESH_CONNECT, TRUE);
    // Timeout super fast once connected, so it goes into async.
    curl_setopt($this->curlSession, CURLOPT_TIMEOUT_MS, 1000);
  }

  /**
   * Performs execution of sync request.
   *
   * @param string $url
   *   URL to sync process starter controller.
   *
   * @return \CleverReach\Infrastructure\Utility\HttpResponse
   *   Http response object that contains response information such
   *   as headers, body and status.
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   *   When request fails.
   */
  private function executeAndReturnResponseForSynchronousRequest($url) {
    $apiResponse = curl_exec($this->curlSession);
    $statusCode = curl_getinfo($this->curlSession, CURLINFO_HTTP_CODE);
    curl_close($this->curlSession);
    if ($apiResponse === FALSE) {
      throw new HttpCommunicationException("Request $url failed.");
    }
    return new HttpResponse(
        $statusCode,
        $this->getHeadersFromCurlResponse($apiResponse),
        $this->getBodyFromCurlResponse($apiResponse)
    );
  }

  /**
   * Extracts headers from curl response.
   *
   * @param string $response
   *   Response retrieved from server.
   *
   * @return array
   *   List of headers.
   */
  private function getHeadersFromCurlResponse($response) {
    $headers = [];
    $headersBodyDelimiter = "\r\n\r\n";
    $headerText = substr($response, 0, strpos($response, $headersBodyDelimiter));
    $headersDelimiter = "\r\n";
    foreach (explode($headersDelimiter, $headerText) as $i => $line) {
      if ($i === 0) {
        $headers[] = $line;
      }
      else {
        list($key, $value) = explode(': ', $line);
        $headers[$key] = $value;
      }
    }
    return $headers;
  }

  /**
   * Gets response body.
   *
   * @param string $response
   *   Response retrieved from server.
   *
   * @return string
   *   Gets body from curl response.
   */
  private function getBodyFromCurlResponse($response) {
    $headersBodyDelimiter = "\r\n\r\n";
    // Number of special signs in delimiter;.
    $bodyStartingPositionOffset = 4;
    return substr(
        $response,
        strpos($response, $headersBodyDelimiter) + $bodyStartingPositionOffset,
        strlen($response)
    );
  }

}
