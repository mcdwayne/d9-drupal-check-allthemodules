<?php

namespace Drupal\akismet\Client;

use Drupal\akismet\Client\Exception\AkismetAuthenticationException;
use Drupal\akismet\Client\Exception\AkismetBadRequestException;
use Drupal\akismet\Client\Exception\AkismetException;
use Drupal\akismet\Client\Exception\AkismetNetworkException;
use Drupal\akismet\Client\Exception\AkismetResponseException;

/**
 * The base class for Akismet client implementations.
 */
abstract class Client {
  /**
   * The Akismet API version, used in HTTP requests.
   */
  const API_VERSION = '1.1';

  /**
   * Network communication failure code: Server could not be reached.
   *
   * @see AkismetNetworkException
   */
  const NETWORK_ERROR = 900;

  /**
   * Client communication failure code: Bad request.
   *
   * @see AkismetBadRequestException
   */
  const REQUEST_ERROR = 400;

  /**
   * Client communication failure code: Authentication error.
   *
   * @see AkismetAuthenticationException
   */
  const AUTH_ERROR = 401;

  /**
   * The Akismet API key to use for request authentication.
   *
   * @var string
   */
  public $key = '';

  /**
   * The Akismet server to communicate with, without the API key subdomain.
   *
   * @var string
   */
  public $server = 'rest.akismet.com';

  /**
   * Maximum number of attempts for a request to the Akismet server.
   *
   * @see Akismet::query()
   * @see Akismet::$requestTimeout
   *
   * @var integer
   */
  public $requestMaxAttempts = 2;

  /**
   * Seconds in which a request to the Akismet server times out.
   *
   * The timeout applies per request. Akismet::query() will retry a request until
   * it reaches Akismet::$requestMaxAttempts. With the default values, a Akismet
   * API call has a total timeout of 6 seconds in case of a server failure.
   *
   * @see Akismet::request()
   * @see Akismet::$requestMaxAttempts
   *
   * @var float
   */
  public $requestTimeout = 3.0;

  /**
   * The last server response.
   *
   * @var AkismetResponse
   */
  public $lastResponse = NULL;

  /**
   * Flag indicating whether to invoke Akismet::writeLog() in Akismet::query().
   *
   * @var bool
   */
  public $writeLog = TRUE;

  /**
   * A list of logged requests.
   *
   * @var array
   */
  public $log = array();

  function __construct() {
    $this->key = $this->loadConfiguration('key');
  }

  /**
   * Loads a configuration value from client-side storage.
   *
   * @param string $name
   *   The configuration setting name to load, one of:
   *   - publicKey: The public API key for Akismet authentication.
   *   - privateKey: The private API key for Akismet authentication.
   *   - expectedLanguages: List of expected language codes for site content.
   *
   * @return mixed
   *   The stored configuration value or NULL if there is none.
   *
   * @see Akismet::saveConfiguration()
   * @see Akismet::deleteConfiguration()
   */
  abstract protected function loadConfiguration($name);

  /**
   * Saves a configuration value to client-side storage.
   *
   * @param string $name
   *   The configuration setting name to save.
   * @param mixed $value
   *   The value to save.
   *
   * @see Akismet::loadConfiguration()
   * @see Akismet::deleteConfiguration()
   */
  abstract protected function saveConfiguration($name, $value);

  /**
   * Deletes a configuration value from client-side storage.
   *
   * @param string $name
   *   The configuration setting name to delete.
   *
   * @see Akismet::loadConfiguration()
   * @see Akismet::saveConfiguration()
   */
  abstract protected function deleteConfiguration($name);

  /**
   * Returns platform and version information about the Akismet client.
   *
   * Retrieves platform and Akismet client version information to send along to
   * Akismet when verifying keys.
   *
   * This information is used to speed up support requests and technical
   * inquiries. The data may also be aggregated to help the Akismet staff to make
   * decisions on new features or the necessity of back-porting improved
   * functionality to older versions.
   *
   * @return array
   *   An associative array containing:
   *   - platformName: The name of the platform/distribution; e.g., "Drupal".
   *   - platformVersion: The version of platform/distribution; e.g., "7.0".
   *   - clientName: The official Akismet client name; e.g., "Akismet".
   *   - clientVersion: The version of the Akismet client; e.g., "7.x-1.0".
   */
  abstract public function getClientInformation();

  /**
   * Returns a string suitable for the User-Agent header of an Akismet request.
   *
   * @return string
   *   A string such as 'Drupal/7.0 / Akismet/7.1'.
   */
  public function getUserAgent() {
    $info = $this->getClientInformation();
    return "{$info['platformName']}/{$info['platformVersion']} | {$info['clientName']}/{$info['clientVersion']}";
  }

  /**
   * Returns the URL of the Drupal site.
   *
   * @return string
   */
  abstract public function getSiteURL();

  /**
   * Writes log messages to a permanent location/storage.
   *
   * Not abstract, since clients are not required to write log messages.
   * However, all clients should permanently store the log messages, as it
   * dramatically improves resolution of support requests filed by users.
   * The log may be written and appended to a file (via file_put_contents()),
   * syslog (on *nix-based systems), or a database.
   *
   * @see Akismet::log
   */
  public function writeLog() {
    // After writing log messages, empty the log.
    $this->purgeLog();
  }

  /**
   * Purges captured log messages.
   *
   * @see Akismet::writeLog()
   */
  final public function purgeLog() {
    $this->log = array();
  }

  /**
   * @param $method
   * @param $path
   * @param $data
   * @param bool $authenticate
   *
   * @return AkismetResponse|int
   */
  public function query($method, $path, $data, $authenticate = TRUE) {
    $data += array(
      'blog' => $this->getSiteURL(),
    );

    $server = $this->getAkismetURL($authenticate);
    $max_attempts = $this->requestMaxAttempts;
    while ($max_attempts-- > 0) {
      try {
        $result = $this->handleRequest($method, $server, $path, $data);
      }
      catch (AkismetBadRequestException $e) {
        // Irrecoverable error, so don't try further.
        break;
      }
      catch (AkismetAuthenticationException $e) {
        // Irrecoverable error, so don't try further.
        break;
      }
      catch (AkismetException $e) {
        // If the requested resource does not exist, or the request was
        // malformed, there is no point in trying further.
        if ($e->getCode() >= 400 && $e->getCode() < 500) {
          break;
        }
      }
      // Unless we have a positive result, try again.
      if (!$this->lastResponse->isError) {
        break;
      }
    }

    // Write all captured log messages.
    if ($this->writeLog) {
      $this->writeLog();
    }

    return $this->lastResponse;
  }

  /**
   * Returns the correct REST server to use for a query.
   *
   * @param bool $authenticate
   *   If TRUE, returns a URL with an API key subdomain. If FALSE, returns a
   *   URL without an API key subdomain (to be used for non-authenticated
   *   calls.)
   *
   * @return string
   */
  protected function getAkismetURL($authenticate) {
    if ($authenticate) {
      $url = 'https://' . $this->key . '.' . $this->server;
    }
    else {
      $url = 'https://' . $this->server;
    }
    return $url;
  }

  /**
   * Prepares an HTTP request to the Akismet server and processes the response.
   *
   * @param $method
   * @param $server
   * @param $path
   * @param $data
   *
   * @return
   * @throws AkismetAuthenticationException
   * @throws AkismetException
   * @throws AkismetNetworkException
   * @throws AkismetResponseException
   */
  protected function handleRequest($method, $server, $path, $data) {
    $time_start = microtime(TRUE);
    if ($method == 'POST') {
      $headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }
    $headers['User-Agent'] = $this->getUserAgent();

    // Append API version to REST endpoint.
    $server .= '/' . self::API_VERSION;

    // Encode data.
    $query = http_build_query($data);

    $response_data = $this->request($method, $server, $path, $query, $headers);
    $this->lastResponse = $response = new AkismetResponse($response_data);

    $time_stop = microtime(TRUE);

    $request_info = array(
      'request' => $method . ' ' . $server . '/' . $path,
      'headers' => $headers,
      'data' => $data,
      'response_code' => $response->code,
      'response_message' => $response->message,
      'response' => $response->body,
      'response_time' => $time_stop - $time_start,
    );
    if ($response->isError) {
      if ($response->code <= 0) {
        throw new AkismetNetworkException('Network error.', self::NETWORK_ERROR, NULL, $this, $request_info);
      }
      if ($response->code === self::REQUEST_ERROR) {
        throw new AkismetBadRequestException($response->message, self::REQUEST_ERROR, NULL, $this, $request_info);
      }
      if ($response->code === self::AUTH_ERROR) {
        throw new AkismetAuthenticationException($response->message, self::REQUEST_ERROR, NULL, $this, $request_info);
      }
      if ($response->code >= 500) {
        throw new AkismetResponseException($response->message, $response->code, NULL, $this, $request_info);
      }
      throw new AkismetException($response->message, $response->code, NULL, $this, $request_info);
    }
    else {
      // No message is logged in case of success.
      $this->log[] = array(
          'severity' => 'debug',
        ) + $request_info;

      return $this->lastResponse;
    }
  }

  /**
   * Performs an HTTP request to the Akismet server.
   *
   * @param string $method
   *   The HTTP method to use; i.e., 'GET', 'POST', or 'PUT'.
   * @param string $server
   *   The base URL of the server to perform the request against; e.g.,
   *   'http://foo.akismet.com'.
   * @param string $path
   *   The REST path/resource to request; e.g., 'site/1a2b3c'.
   * @param string $query
   *   (optional) A prepared string of HTTP query parameters to append to $path
   *   for $method GET, or to use as request body for $method POST.
   * @param array $headers
   *   (optional) An associative array of HTTP request headers to send along
   *   with the request.
   *
   * @return object
   *   An object containing response properties:
   *   - code: The HTTP status code as integer returned by the Akismet server.
   *   - message: The HTTP status message string returned by the Akismet server,
   *     or NULL if there is no message.
   *   - headers: An associative array containing the HTTP response headers
   *     returned by the Akismet server. Header name keys are expected to be
   *     lower-case; i.e., "content-type" instead of "Content-Type".
   *   - body: The HTTP response body string returned by the Akismet server, or
   *     NULL if there is none.
   *
   * @see Akismet::handleRequest()
   */
  abstract protected function request($method, $server, $path, $query = NULL, array $headers = array());


  /**
   * Verifies an API key with Akismet.
   *
   * @param string $key
   *   The API key to be checked, if different than the one in the constructor.
   *
   * @return boolean|int
   *   TRUE or FALSE if we got a response from the server; otherwise, the error
   *   code.
   */
  public function verifyKey($key) {
    if (empty($key)) {
      $key = $this->key;
    }
    $parameters = [
      'key' => $key,
    ];
    $response = $this->query('POST', 'verify-key', $parameters, FALSE);
    $body = $response->body;
    if ($body === 'valid') {
      return TRUE;
    }
    if ($body === 'invalid') {
      return FALSE;
    }
    return ($response->code);
  }

  /**
   * Checks user-submitted content with Akismet.
   *
   * @param array $data
   *   An associative array containing any of the keys:
   *   - blog: The URL of this site.
   *   - user_ip: The IP address of the text submitter.
   *   - user_agent: The user-agent string of the web browser submitting the
   *     text.
   *   - referrer: The HTTP_REFERER value.
   *   - permalink: The permanent URL where the submitted text can be found.
   *   - comment_type: A description of the type of content being checked:
   *     https://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/
   *   - comment_author: The (real) name of the content author.
   *   - comment_author_email: The email address of the content author.
   *   - comment_author_url: The URL (if any) that the content author provided.
   *   - comment_content: The body of the content. If the content consists of
   *     multiple fields, concatenate them into one postBody string, separated
   *     by " \n" (space and line-feed).
   *   - comment_date_gmt: The date the content was submitted.
   *   - comment_post_modified_gmt: (For comments only) The creation date of the
   *     post being commented on.
   *   - blog_lang: The languages in use on this site, in ISO 639-1 format. Ex:
   *     "en, fr_ca".
   *   - blog_charset: The character encoding in use for the values being
   *     submitted.
   *   - user_role: The role of the user who submitted the comment. Optional.
   *     Should be 'administrator' for site administrators; submitting a value
   *     of 'administrator' will guarantee that Akismet sees the content as ham.
   *   - server: The contents of $_SERVER, to be added to the request.
   *
   * @return int|array
   *   On failure, the status code. On success, an associative array keyed as
   *   follows:
   *   - guid: The GUID returned by Akismet.
   *   - classification: the spam classification ('ham', 'spam', or 'unsure').
   */
  public function checkContent(array $data = array()) {
    if (empty($data['server'])) {
      $server = $_SERVER;
    }
    else {
      $server = static::prepareServerVars($data['server']);
      unset($data['server']);
    }
    $parameters = $data + $server;
    $result = $this->query('POST', 'comment-check', $parameters);
    if ($result->isError) {
      return $result->code;
    }

    $guid = $result->guid();
    $body = $result->body;
    if ($body === 'false') {
      return array(
        'classification' => 'ham',
        'guid' => $guid,
      );
    }

    if (!empty($result->headers['x-akismet-pro-tip'])) {
      return array(
        'classification' => 'spam',
        'guid' => $guid,
      );
    }

    if ($body === 'true') {
      return array(
        'classification' => 'unsure',
        'guid' => $guid,
      );
    }

    // If we get to this point, there was an error of some kind that we didn't
    // catch.
    return 500;
  }

  /**
   * Sends "this is spam" or "this is ham" feedback to Akismet.
   *
   * @param array $data
   *   The session data saved from the original request.
   * @param string $feedback
   *   Either 'spam' or 'ham'.
   *
   * @return int|bool
   *   On success, TRUE. On failure, the error code.
   */
  public function sendFeedback(array $data, $feedback) {
    if (isset($data['server'])) {
      $server = static::prepareServerVars($data['server']);
      unset($data['server']);
    }
    else {
      $server = [];
    }
    $parameters = (array) $data + (array) $server;

    if ($feedback === 'spam') {
      $result = $this->query('POST', 'submit-spam', $parameters);
    }
    else {
      $result = $this->query('POST', 'submit-ham', $parameters);
    }
    if ($result->isError) {
      return $result->code;
    }
    return TRUE;
  }

  /**
   * Removes possibly sensitive entries from an array of $_SERVER data.
   *
   * @param array $server_vars
   *   The contents of $_SERVER.
   *
   * @return array
   *   An array of $_SERVER variables with sensitive entries removed.
   */
  static public function prepareServerVars($server_vars) {
    static $safe_to_send = array(
      'CONTENT_LENGTH',
      'CONTENT_TYPE',
      'HTTP_ACCEPT',
      'HTTP_ACCEPT_CHARSET',
      'HTTP_ACCEPT_ENCODING',
      'HTTP_ACCEPT_LANGUAGE',
      'HTTP_REFERER',
      'HTTP_USER_AGENT',
      'REMOTE_ADDR',
      'REMOTE_PORT',
      'SCRIPT_URI',
      'SCRIPT_URL',
      'SERVER_ADDR',
      'SERVER_NAME',
      'REQUEST_METHOD',
      'REQUEST_URI',
      'SCRIPT_NAME'
    );

    return array_intersect_key($server_vars, array_flip($safe_to_send));
  }

}
