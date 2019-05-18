<?php

namespace Drupal\plus\Http;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Class Client.
 */
class Client extends GuzzleClient implements ClientInterface {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var \Drupal\plus\Http\UserAgent
   */
  protected $userAgent;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config = []) {
    if (isset($config['cache_backend'])) {
      if (is_string($config['cache_backend'])) {
        $config['cache_backend'] = \Drupal::service($config['cache_backend']);
      }
      if (!($config['cache_backend'] instanceof CacheBackendInterface)) {
        throw new \InvalidArgumentException('The provided configuration for "cache_backend" must be an instance of \Drupal\Core\Cache\CacheBackendInterface or the name of a service that implements this interface.');
      }
      $cacheBackend = $config['cache_backend'];
    }
    else {
      $cacheBackend = \Drupal::service('cache.http_client');
    }

    $this->cache = $cacheBackend;

    $this->userAgent = new UserAgent(isset($config['headers']['User-Agent']) ? $config['headers']['User-Agent'] : NULL);

    $config['headers']['User-Agent'] = $this->userAgent;

    // Ensure there is a TTL.
    if (!isset($config['ttl']) || !is_numeric($config['ttl'])) {
      $config['ttl'] = 0;
    }

    parent::__construct($config);
  }

  /**
   * {@inheritdoc}
   */
  public function addUserAgent($user_agent, $version = NULL, $url = NULL) {
    $this->userAgent->add($user_agent, $version, $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserAgent() {
    return $this->userAgent;
  }

  /**
   * {@inheritdoc}
   */
  public function cacheableRequest($method, $uri = '', array $options = []) {
    $options = $this->prepareDefaults($options);

    // Allow Url objects to be passed.
    if ($uri instanceof Url) {
      $uri = $uri->toString();
    }

    $cid = $options['expires'] ? implode(':', [strtolower($method), $uri, $options['hash']]) : FALSE;

    // Use a cached response, if one exists.
    if ($cid && ($cache = $this->cache->get($cid)) && isset($cache->data) && $cache->data instanceof CacheableResponse) {
      return $cache->data;
    }

    try {
      $response = $this->request($method, $uri, $options);
    }
    catch (RequestException $e) {
      $response = $e->getResponse() ?: new Response(500, [], $e->getMessage());
    }
    catch (GuzzleException $e) {
      if ($e instanceof RequestException) {
        $response = $e->getResponse();
      }
      if (!isset($response)) {
        $response = new Response(500, [], $e->getMessage());
      }
    }

    // Transform the response into a CacheableResponse and then cache it.
    $body = $response->getBody();
    $cacheable_response = new CacheableResponse($body ? $body->getContents() : '', $response->getStatusCode(), $response->getHeaders());

    // Override any expiration sent.
    $expires = new \DateTime();
    $expires->setTimestamp($options['expires']);
    $cacheable_response->setExpires($expires);

    // Cache the response.
    if ($cid) {
      $this->cache->set($cid, $cacheable_response, $options['expires']);
    }

    return $cacheable_response;
  }

  /**
   * {@inheritdoc}
   *
   * Note: unfortunately Symfony restricts access to this method using private.
   */
  protected function prepareDefaults($options) {
    $defaults = $this->getConfig();

    if (!empty($defaults['headers'])) {
      // Default headers are only added if they are not present.
      $defaults['_conditional'] = $defaults['headers'];
      unset($defaults['headers']);
    }

    // Special handling for headers is required as they are added as
    // conditional headers and as headers passed to a request ctor.
    if (array_key_exists('headers', $options)) {
      // Allows default headers to be unset.
      if ($options['headers'] === NULL) {
        $defaults['_conditional'] = NULL;
        unset($options['headers']);
      }
      elseif (!is_array($options['headers'])) {
        throw new \InvalidArgumentException('headers must be an array');
      }
    }

    // Shallow merge defaults underneath options.
    $result = $options + $defaults;

    // Remove null values.
    foreach ($result as $k => $v) {
      if ($v === NULL) {
        unset($result[$k]);
      }
    }

    // Retrieve or create a new expiration timestamp based on the TTL, but
    // don't add it to the array just yet because it would affect the hash that
    // is about to be created based on the array.
    if (!empty($result['expires'])) {
      $expires = (int) $result['expires'] ?: 0;
      unset($result['expires']);
    }
    else {
      $expires = !empty($result['ttl']) ? $result['ttl'] + \Drupal::time()->getRequestTime() : 0;
    }

    // Create a hash of the array.
    $result['hash'] = $expires ? Crypt::hashBase64(serialize(array_diff_key($result, ['handler' => TRUE]))) : '';

    // Now set the expiration timestamp.
    $result['expires'] = $expires;

    return $result;
  }


}
