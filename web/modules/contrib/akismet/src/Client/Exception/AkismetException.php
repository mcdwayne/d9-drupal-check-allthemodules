<?php

namespace Drupal\akismet\Client\Exception;
use Drupal\akismet\Client\Client;

/**
 * A catchable Akismet exception.
 *
 * The Akismet class internally uses exceptions to handle HTTP request errors
 * within the Akismet::handleRequest() method. All exceptions thrown in the
 * Akismet class and derived classes should be instances of the AkismetException
 * class if they pertain to errors that can be catched/handled within the class.
 * Other errors should not use the AkismetException class and handled
 * differently.
 *
 * No AkismetException is supposed to pile up as a user-facing fatal error. All
 * functions that invoke Akismet::handleRequest() have to catch Akismet
 * exceptions.
 *
 * @see Akismet::query()
 * @see Akismet::handleRequest()
 *
 * @param $message
 *   The Exception message to throw.
 * @param $code
 *   The Exception code.
 * @param $previous
 *   (optional) The previous Exception, if any.
 * @param $instance
 *   The Akismet class instance the Exception is thrown in.
 * @param $arguments
 *   (optional) A associative array containing information about a performed
 *   HTTP request that failed:
 *   - request: (string) The HTTP method and URI of the performed request; e.g.,
 *     "GET http://server.akismet.com/v1/foo/bar". In case of GET requests, do
 *     not add query parameters to the URI; pass them in 'data' instead.
 *   - data: (array) An associative array containing HTTP GET/POST/PUT request
 *     query parameters that were sent to the server.
 *   - response: (mixed) The server response, either as string, or the already
 *     parsed response; i.e., an array.
 */
class AkismetException extends \Exception {
  /**
   * @var \Drupal\akismet\Client\Client $client
   */
  protected $client;

  /**
   * The severity of this exception.
   *
   * By default, all exceptions should be logged and appear as errors (unless
   * overridden by a later log entry).
   *
   * @var string
   */
  protected $severity = 'error';

  /**
   * Overrides Exception::__construct().
   */
  function __construct($message = '', $code = 0, \Exception $previous = NULL, Client $client, array $request_info = array()) {
    // Fatal error on PHP <5.3 when passing more arguments to Exception.
    if (version_compare(phpversion(), '5.3') >= 0) {
      parent::__construct($message, $code, $previous);
    }
    else {
      parent::__construct($message, $code);
    }
    $this->client = $client;

    // Set the error code on the Akismet class.
    $client->lastResponseCode = $code;

    // Log the exception.
    // To aid Akismet technical support, include the IP address of the server we
    // tried to reach in case a request fails.
    // PHP's native gethostbyname() is available on all platforms, but its DNS
    // lookup and caching behavior is undocumented and unclear. User comments on
    // php.net mention that it does not have an own cache and also does not use
    // the OS/platform's native DNS name resolver. Due to that, we only use it
    // under error conditions.
    $message = array(
      'severity' => $this->severity,
      'message' => 'Error @code: %message (@server-ip)',
      'arguments' => array(
        '@code' => $code,
        '%message' => $message,
        '@server-ip' => gethostbyname($client->server),
      ),
    );
    // Add HTTP request information, if available.
    if (!empty($request_info)) {
      $message += $request_info;
    }
    $client->log[] = $message;
  }
}
