<?php

namespace Drupal\cloudconvert;

use Drupal\cloudconvert\Exceptions\ApiBadRequestException;
use Drupal\cloudconvert\Exceptions\ApiConversionFailedException;
use Drupal\cloudconvert\Exceptions\ApiException;
use Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException;
use Drupal\cloudconvert\Exceptions\InvalidParameterException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Base Wrapper to manage login and exchanges with CloudConvert API.
 *
 * Http connections use guzzle http client api and result of request are
 * object from this http wrapper.
 */
class Api {

  /**
   * Url to communicate with CloudConvert API.
   *
   * @var string
   */
  private $endpoint = 'api.cloudconvert.com';

  /**
   * Protocol (http or https) to communicate with CloudConvert API.
   *
   * @var string
   */
  private $protocol = 'https';

  /**
   * API Key of the current application.
   *
   * @var string
   */
  private $apiKey;

  /**
   * Contain http client connection.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Construct a new wrapper instance.
   *
   * @param string $apiKey
   *   Key of your application.
   *   You can get your API Key on https://cloudconvert.com/user/profile.
   * @param \GuzzleHttp\Client $httpClient
   *   Instance of http client.
   *
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   */
  public function __construct($apiKey, Client $httpClient) {
    if ($apiKey === NULL) {
      throw new InvalidParameterException('API Key parameter is empty');
    }

    $this->apiKey = $apiKey;
    $this->httpClient = $httpClient;
  }

  /**
   * Wrap call to CloudConvert APIs for PUT requests.
   *
   * @param string $path
   *   Path ask inside api.
   * @param string $content
   *   Content to send inside body of request.
   * @param bool $isAuthenticated
   *   True if the request uses authentication.
   *
   * @return mixed
   *   Result from the request.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function put($path, $content, $isAuthenticated = TRUE) {
    return $this->rawCall('PUT', $path, $content, $isAuthenticated);
  }

  /**
   * This is the main method of this wrapper.
   *
   * It will sign a given query and return its result.
   *
   * @param string $method
   *   HTTP method of request (GET,POST,PUT,DELETE).
   * @param string $path
   *   Relative url of API request.
   * @param string|resource|array $content
   *   Body of the request.
   * @param bool $isAuthenticated
   *   True if the request uses authentication.
   *
   * @return mixed
   *   Result from the request.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function rawCall($method, $path, $content = NULL, $isAuthenticated = TRUE) {
    $url = $path;
    if (strpos($path, '//') === 0) {
      $url = $this->protocol . ':' . $path;
    }
    elseif (strpos($url, 'http') !== 0) {
      $url = $this->protocol . '://' . $this->endpoint . $path;
    }

    $options = [
      'query' => [],
      'body' => NULL,
      'headers' => [],
    ];

    if (\is_array($content)) {
      if ($method === 'GET') {
        $options['query'] = $content;
      }
      else {
        $body = json_encode($content);
        $options['body'] = \GuzzleHttp\Psr7\stream_for($body);
        $options['headers']['Content-Type'] = 'application/json; charset=utf-8';
      }
    }
    elseif (\is_resource($content) && $method === 'PUT') {
      $options['body'] = \GuzzleHttp\Psr7\stream_for($content);
    }

    if ($isAuthenticated) {
      $options['headers']['Authorization'] = 'Bearer ' . $this->apiKey;
    }

    try {
      $response = $this->httpClient->request($method, $url, $options);
      if ($response->getHeader('Content-Type') && strpos($response->getHeader('Content-Type')[0], 'application/json') === 0) {
        return json_decode($response->getBody(), TRUE);
      }

      if ($response->getBody()->isReadable()) {
        return $response->getBody();
      }
    }
    catch (RequestException $e) {
      if (!$e->getResponse()) {
        throw $e;
      }

      $json = json_decode($e->getResponse()->getBody(), TRUE);

      if (JSON_ERROR_NONE !== json_last_error()) {
        throw new \RuntimeException('Error parsing JSON response');
      }

      if (isset($json['message']) || isset($json['error'])) {
        $msg = isset($json['error']) ? $json['error'] : $json['message'];
        $code = $e->getResponse()->getStatusCode();
        if ($code === 400) {
          throw new ApiBadRequestException($msg, $code);
        }

        if ($code === 422) {
          throw new ApiConversionFailedException($msg, $code);
        }

        if ($code === 503) {
          throw new ApiTemporaryUnavailableException(
            $msg,
            $code,
            $e->getResponse()->getHeader('Retry-After') ? $e->getResponse()
              ->getHeader('Retry-After')[0] : NULL
          );
        }

        throw new ApiException($msg, $code);
      }

      throw $e;
    }

    return FALSE;
  }

  /**
   * Wrap call to CloudConvert APIs for DELETE requests.
   *
   * @param string $path
   *   Path ask inside api.
   * @param string $content
   *   Content to send inside body of request.
   * @param bool $isAuthenticated
   *   True if the request uses authentication.
   *
   * @return mixed
   *   Result of the request.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function delete($path, $content = NULL, $isAuthenticated = TRUE) {
    return $this->rawCall('DELETE', $path, $content, $isAuthenticated);
  }

  /**
   * Get the current API Key.
   *
   * @return string
   *   API key.
   */
  public function getApiKey() {
    return $this->apiKey;
  }

  /**
   * Return instance of http client.
   *
   * @return \GuzzleHttp\Client
   *   Guzzle HTTP Client.
   */
  public function getHttpClient() {
    return $this->httpClient;
  }

  /**
   * Create a new Process.
   *
   * @param Parameters $parameters
   *   Parameters for creating the Process.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function createProcess(Parameters $parameters) {
    $result = $this->post('/process', $parameters->getParameters(), TRUE);

    if (!isset($result['id']) || !isset($result['url'])) {
      throw new InvalidParameterException('The parameters are not valid.');
    }

    return new Process($this, $result['id'], $result['url']);
  }

  /**
   * Wrap call to CloudConvert APIs for POST requests.
   *
   * @param string $path
   *   Path ask inside API.
   * @param string|array $content
   *   Content to send inside body of request.
   * @param bool $isAuthenticated
   *   True if the request uses authentication.
   *
   * @return mixed
   *   Result of the request.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function post($path, $content, $isAuthenticated = TRUE) {
    return $this->rawCall('POST', $path, $content, $isAuthenticated);
  }

  /**
   * Create a new Process.
   *
   * @param string $url
   *   Url.
   * @param \Drupal\cloudconvert\Parameters $parameters
   *   Parameters for creating the Process.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \Drupal\cloudconvert\Exceptions\InvalidParameterException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getProcess($url, Parameters $parameters) {
    $result = $this->get($url, $parameters->getParameters(), FALSE);
    return new Process($this, $result['id'], $result['url']);
  }

  /**
   * Wrap call to CloudConvert APIs for GET requests.
   *
   * @param string $path
   *   Path ask inside API.
   * @param string $content
   *   Content to send inside body of request.
   * @param bool $isAuthenticated
   *   True if the request uses authentication.
   *
   * @return mixed
   *   Result of the request.
   *
   * @throws \RuntimeException
   * @throws \GuzzleHttp\Exception\RequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiBadRequestException
   * @throws \Drupal\cloudconvert\Exceptions\ApiConversionFailedException
   * @throws \Drupal\cloudconvert\Exceptions\ApiException
   * @throws \Drupal\cloudconvert\Exceptions\ApiTemporaryUnavailableException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function get($path, $content = NULL, $isAuthenticated = TRUE) {
    return $this->rawCall('GET', $path, $content, $isAuthenticated);
  }

}
