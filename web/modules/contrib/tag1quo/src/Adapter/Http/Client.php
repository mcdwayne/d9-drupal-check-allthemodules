<?php

namespace Drupal\tag1quo\Adapter\Http;

use Drupal\tag1quo\Adapter\Core\Core;
use Drupal\tag1quo\Adapter\Logger\Logger;
use Drupal\tag1quo\VersionedClass;

/**
 * Class Client.
 *
 * @internal This class is subject to change.
 *
 * @method Response|JsonResponse get(string|\Drupal\Core\TypedData\Type\UriInterface $uri, array $options = array())
 * @method Response|JsonResponse head(string|\Drupal\Core\TypedData\Type\UriInterface $uri, array $options = array())
 * @method Response|JsonResponse put(string|\Drupal\Core\TypedData\Type\UriInterface $uri, array $options = array())
 * @method Response|JsonResponse post(string|\Drupal\Core\TypedData\Type\UriInterface $uri, array $options = array())
 * @method Response|JsonResponse patch(string|\Drupal\Core\TypedData\Type\UriInterface $uri, array $options = array())
 * @method Response|JsonResponse delete(string|\Drupal\Core\TypedData\Type\UriInterface $uri, array $options = array())
 */
abstract class Client extends VersionedClass {

  /**
   * Provides the default cURL options.
   *
   * @var array
   */
  protected static $defaultCurlOptions;

  /**
   * Indicates whether gzip is available.
   *
   * @var bool
   */
  protected static $hasGzip;

  /**
   * @var \Drupal\tag1quo\Adapter\Core\Core
   */
  protected $core;

  /**
   * Flag indicating whether to use cURL.
   *
   * @var bool
   */
  protected $useCurl;

  /**
   * Client constructor.
   *
   * @param \Drupal\tag1quo\Adapter\Core\Core $core
   *   A Core adapter instance.
   */
  public function __construct(Core $core) {
    $this->core = $core;
  }

  /**
   * {@inheritdoc}
   */
  public function __call($method, $args) {
    if (count($args) < 1) {
      throw new \InvalidArgumentException('Magic request methods require a URI and optional options array');
    }
    $uri = $args[0];
    $options = isset($args[1]) ? $args[1] : array();
    return $this->request($method, $uri, $options);
  }

  /**
   * Creates a new HTTP Client.
   *
   * @param \Drupal\tag1quo\Adapter\Core\Core $core
   *   A Core adapter instance.
   *
   * @return static
   */
  public static function create(Core $core) {
    return static::createVersionedStaticInstance([$core]);
  }

  /**
   * Creates a response.
   *
   * @param string $content
   *   The content returned from the HTTP client.
   * @param int $statusCode
   *   The status code returned from the HTTP client.
   * @param array $headers
   *   The headers returned from the HTTP client.
   *
   * @return \Drupal\tag1quo\Adapter\Http\JsonResponse|\Drupal\tag1quo\Adapter\Http\Response
   *   A JSON response object (if returned response contained JSON) or a normal
   *   Response object otherwise.
   */
  protected function createResponse($content, $statusCode, array $headers = array()) {
    if (isset($headers['Content-Type']) && strpos(is_array($headers['Content-Type']) ? reset($headers['Content-Type']) : $headers['Content-Type'], 'json') !== FALSE) {
      return new JsonResponse($content, $statusCode, $headers);
    }
    return new Response($content, $statusCode, $headers);
  }

  /**
   * The default cURL options.
   *
   * @return array
   */
  protected function defaultCurlOptions() {
    if (static::$defaultCurlOptions === NULL) {
      static::$defaultCurlOptions = array(
        CURLOPT_VERBOSE => FALSE,
        CURLOPT_CONNECTTIMEOUT => 45,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_FRESH_CONNECT => TRUE,
        CURLOPT_HTTPPROXYTUNNEL => FALSE,
        CURLOPT_SSL_VERIFYPEER => TRUE,
      );
      if (version_compare(phpversion(), '7.0.7', '>=')) {
        static::$defaultCurlOptions[CURLOPT_SSL_VERIFYSTATUS] = TRUE;
      }
    }
    return static::$defaultCurlOptions;
  }

  /**
   * Perform the actual request.
   *
   * @param \Drupal\tag1quo\Adapter\Http\Request $request
   *   The request.
   *
   * @return \Drupal\tag1quo\Adapter\Http\Response
   *   A response object.
   */
  abstract protected function doRequest(Request $request);

  /**
   * Finalizes the request right before its about to be sent.
   *
   * @param \Drupal\tag1quo\Adapter\Http\Request $request
   *   The Request object to finalize.
   */
  protected function finalizeRequest(Request $request) {
    // Backport JSON support for older (non-Guzzle based) HTTP clients.
    if ($json = $request->options->get('json')) {
      if (!$request->query->has('_format')) {
        $request->query->set('_format', 'json');
      }
      if (!$request->headers->has('Accept')) {
        $request->headers->set('Accept', 'application/json');
      }
      if (!$request->headers->has('Content-Type')) {
        $request->headers->set('Content-Type', 'application/json');
      }
      if (is_array($json)) {
        $json = $this->core->jsonEncode($json);
      }
      $request->options->set('body', $json);
      $request->options->remove('json');
    }

    // Encode the body.
    if ($this->hasGzip() && $request->options->get('gzip')) {
      if (!$request->headers->has('Accept-Encoding')) {
        $request->headers->set('Accept-Encoding', 'gzip');
      }
      if ($body = $request->options->get('body')) {
        $request->options->set('body', gzencode($body, 9));
        if (!$request->headers->has('Content-Encoding')) {
          $request->headers->set('Content-Encoding', 'gzip');
        }
      }
    }

    // Merge header cookies with cookies.
    if ($request->headers->has('Cookie')) {
      $request->cookies->replace(array_merge($request->cookies->all(), $request->headers->get('Cookie', array())));
    }

    // Merge cookies into header.
    if ($cookies = $request->cookies->all()) {
      $cookies = http_build_query($cookies, NULL, ';');
      $request->headers->set('Cookie', $cookies);
    }
    else {
      $request->headers->remove('Cookie');
    }

    // Merge headers into options.
    if ($headers = $request->headers->all()) {
      $request->options->set('headers', $headers);
    }
    else {
      $request->options->remove('headers');
    }
  }

  /**
   * Perform the actual request, using cURL.
   *
   * @param \Drupal\tag1quo\Adapter\Http\Request $request
   *   The request.
   *
   * @return \Drupal\tag1quo\Adapter\Http\Response
   *   A response object.
   */
  protected function doCurlRequest(Request $request) {
    $ch = curl_init($request->getUri());

    // Immediately return if unable to open a cURL resource.
    if (!$ch || !is_resource($ch) || get_resource_type($ch) !== 'curl') {
      $this->core->logger()->error('Unable to create a cURL resource. Using Drupal HTTP client.');
      return $this->doRequest($request);
    }

    // Retrieve cURL options. Note: need to use the mergeDeepArray method to
    // preserve the integer keys because they are unique cURL option integers.
    $curl_options = $this->core->mergeDeepArray(array($this->defaultCurlOptions(), $this->core->config('tag1quo.settings')->get('curl.options', array())), TRUE);

    // Extract the cURL default headers.
    $curlHeaders = isset($curl_options[CURLOPT_HTTPHEADER]) ? $curl_options[CURLOPT_HTTPHEADER] : array();
    unset($curl_options[CURLOPT_HTTPHEADER]);

    // Now set cURL options.
    curl_setopt_array($ch, $curl_options);

    // Merge cURL and request headers.
    $requestHeaders = $this->core->mergeDeep($curlHeaders, $request->headers->all());

    // Convert request headers into a single string value.
    foreach($requestHeaders as $name => $value) {
      if (is_array($value)) {
        $value = implode(', ', $value);
      }
      $requestHeaders[$name] = "$name: $value";
    }

    // Now set the headers.
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($requestHeaders));

    // Set a specific timeout.
    if ($timeout = $request->options->get('timeout')) {
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    }

    // Ensure the response data is always returned.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    // Set the method.
    $method = $request->getMethod();
    switch ($method) {
      case 'GET':
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        break;

      case 'POST':
        curl_setopt($ch, CURLOPT_POST, TRUE);
        break;

      case 'HEAD':
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        break;

      default:
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    // Set data.
    if ($body = $request->options->get('body')) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    // Capture response headers.
    $responseHeaders = array();
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $raw) use (&$responseHeaders) {
      $len = strlen($raw);
      $parts = explode(':', $raw, 2);
      if (count($parts) === 2) {
        list($header, $value) = $parts;
        $header = trim($header);
        $value = trim($value);
        if ($header && $value !== '') {
          if (!array_key_exists($header, $responseHeaders)) {
            $responseHeaders[$header] = [$value];
          }
          else {
            $responseHeaders[$header][] = $value;
          }
        }
      }
      return $len;
    });

    // Never include the headers in the output (since it's captured above).
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    // Invoke cURL.
    $data = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Log the cURL info.
    if ($request->options->get('debug')) {
      $this->core->logger()->debug('[DEBUG] cURL Info: <pre><code>@curlinfo</code></pre>', array(
        '@curlinfo' => $this->core->jsonEncode(curl_getinfo($ch), TRUE),
      ));
    }

    curl_close($ch);

    return $this->createResponse($data, $statusCode, $responseHeaders);
  }

  /**
   * Indicates whether gzip is available.
   *
   * @return bool
   */
  protected function hasGzip() {
    if (static::$hasGzip === NULL) {
      static::$hasGzip = extension_loaded('zlib') && function_exists('gzencode');
    }
    return static::$hasGzip;
  }

  /**
   * Sends a request via an HTTP client.
   *
   * @param string $method
   *   The HTTP method to use.
   * @param string $uri
   *   The URI to send a request to.
   * @param array $options
   *   The options to pass along to the HTTP client.
   *
   * @return \Drupal\tag1quo\Adapter\Http\Response
   *   A Response object.
   */
  public function request($method, $uri, array $options = array()) {
    // Create a new request object.
    $request = new Request($method, $uri, $options);

    // Prepare the request before its sent.
    $this->prepareRequest($request);

    // Finalize the request.
    $this->finalizeRequest($request);

    // Do a cURL request, if necessary.
    if ($request->options->get('curl', $this->useCurl())) {
      $response = $this->doCurlRequest($request);
    }
    // Otherwise, use Drupal's HTTP client.
    else {
      $response = $this->doRequest($request);
    }

    $debug = $this->core->inDebugMode();
    $success = $response->isSuccessful();
    if (!$success || $debug) {
      $this->core->logger()->log($debug ? Logger::DEBUG : Logger::ERROR, $this->core->t('[DEBUG] Response: <pre><code>@response</code></pre>', array(
        '@response' => $this->core->jsonEncode($response->toArray(), TRUE),
      )));
    }

    return $response;
  }

  /**
   * Prepares the request before it's sent.
   *
   * @param \Drupal\tag1quo\Adapter\Http\Request $request
   *   The Request object.
   */
  protected function prepareRequest(Request $request) {
    // Determine if request is in debug mode.
    if ($request->options->get('debug', $this->core->inDebugMode())) {
      // Set a longer default timeout when in debug mode.
      if (!$request->options->has('timeout')) {
        $request->options->set('timeout', 300);
      }
      if ($xdebugSession = $this->core->config('tag1quo.settings')->get('debug.xdebug.session', 'PHPSTORM')) {
        $request->cookies->set('XDEBUG_SESSION', $xdebugSession);
      }
    }
  }

  /**
   * Indicates whether to use cURL.
   *
   * @return bool
   */
  protected function useCurl() {
    if ($this->useCurl === NULL) {
      $this->useCurl = function_exists('curl_init') && $this->core->config('tag1quo.settings')->get('curl.enabled', FALSE);
    }
    return $this->useCurl;
  }

}
