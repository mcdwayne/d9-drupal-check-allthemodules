<?php

namespace Drupal\drd;

use Drupal\drd\Entity\DomainInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

/**
 * Class HttpRequest.
 *
 * @package Drupal\drd
 */
class HttpRequest {

  /**
   * Get the current library version for the installed DRD module.
   */
  public static function getVersion() {
    return LibraryBuild::DRD_LIBRARY_VERSION;
  }

  /**
   * The domain entity to communicate with.
   *
   * @var \Drupal\drd\Entity\DomainInterface
   */
  protected $domain;

  /**
   * The query to submit.
   *
   * @var string
   */
  protected $query = '';

  /**
   * The options to use for request.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The response for the request.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  /**
   * The return status code.
   *
   * @var int
   */
  protected $statusCode;

  /**
   * Flag if the remote domain properly supports DRD.
   *
   * @var bool
   */
  protected $remoteIsDrd;

  /**
   * Set the DRD domain entity.
   *
   * @param \Drupal\drd\Entity\DomainInterface $domain
   *   The domain entity.
   *
   * @return $this
   */
  public function setDomain(DomainInterface $domain) {
    $this->domain = $domain;
    return $this;
  }

  /**
   * Set the query.
   *
   * @param string $query
   *   The query.
   *
   * @return $this
   */
  public function setQuery($query) {
    $this->query = $query;
    return $this;
  }

  /**
   * Set a request option.
   *
   * @param string $key
   *   Key for the option.
   * @param string $value
   *   Value of the option.
   *
   * @return $this
   */
  public function setOption($key, $value) {
    $this->options[$key] = $value;
    return $this;
  }

  /**
   * Get the request's response.
   *
   * @return string|bool
   *   The body of the response if successful, FALSE otherwise.
   *
   * @throws \Exception
   */
  public function getResponse() {
    if (empty($this->response)) {
      // We haven't received anything from remote.
      return FALSE;
    }
    if (!$this->isRemoteDrd()) {
      // We received something from remote, but not from DRD remote.
      throw new \Exception('Remote domain does not respond as DRD.');
    }

    // Let's decode the response from DRD remote.
    $body = base64_decode($this->response->getBody()->getContents());
    if ($body === FALSE) {
      // Reponse can not be decoded.
      throw new \Exception('Received unexpected content.');
    }

    // Return the actual response.
    return $body;
  }

  /**
   * Get the response headers.
   *
   * @return bool|\string[][]
   *   The response headers.
   */
  public function getResponseHeaders() {
    if (empty($this->response)) {
      // We haven't received anything from remote.
      return FALSE;
    }
    return $this->response->getHeaders();
  }

  /**
   * Get the response status code.
   *
   * @return int
   *   The status code.
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * Get the flag if remote domain properly supports DRD.
   *
   * @return bool
   *   TRUE if everything is working fine.
   */
  public function isRemoteDrd() {
    return $this->remoteIsDrd;
  }

  /**
   * Submit the request and analyse the response.
   */
  public function request() {
    $url = $this->domain->buildUrl($this->query);
    $this->options['headers'] = $this->domain->getHeader();
    $this->options['headers']['X-Drd-Version'] = self::getVersion();
    $jar = new CookieJar();
    $cookies = $this->domain->getCookies();
    foreach ($cookies as $cookie) {
      $jar->setCookie(SetCookie::fromString($cookie));
    }
    $this->options['cookies'] = $jar;
    $this->statusCode = -1;
    $this->remoteIsDrd = FALSE;
    try {
      $client = new Client(['base_uri' => $url->toUriString()]);
      $this->response = $client->request('post', NULL, $this->options);
      $this->statusCode = $this->response->getStatusCode();
      $this->remoteIsDrd = (
        $this->statusCode == 200 &&
        $this->response->getHeaderLine('content-type') == 'text/plain; charset=utf-8' &&
        $this->response->getHeaderLine('x-drd-agent') == self::getVersion()
      );
      $new_cookies = $this->response->getHeader('set-cookie');
      if (empty($new_cookies)) {
        $new_cookies = $cookies;
      }
      $this->domain->setCookies($new_cookies);
    }
    catch (\Exception $ex) {
      $this->statusCode = $ex->getCode();
    }
  }

}
