<?php

namespace Drupal\akismet\Client;

/**
 * Represents a response from the Akismet API.
 */
class AkismetResponse {

  /**
   * @var int $code
   */
  protected $code;

  /**
   * @var string $message
   */
  protected $message;

  /**
   * Associative array of response headers, keyed by header name.
   *
   * @var array
   */
  protected $headers;

  /**
   * The body of the response. Usually one word: 'true', 'false', 'valid'.
   *
   * @var string
   */
  protected $body;

  /**
   * A flag indicating whether the response indicated an error.
   *
   * @var bool
   */
  protected $isError;

  public function __construct($data) {
    $this->headers = $data->headers;
    $this->body = $data->body->getContents();
    $this->code = $data->code;

    // Determine basic error condition based on HTTP status code.
    $this->isError = ($this->code < 200 || $this->code >= 300);

    // The Akismet API returns 200 OK even when there's an error, so it's
    // hard to be sure what kind of response this is.

    // One way we can be sure the request was malformed is if we receive the
    // header 'X-Akismet-Debug-Help'.
    if (!empty($this->headers['x-akismet-debug-help'])) {
      $this->isError = TRUE;
      $this->code = Client::REQUEST_ERROR;
      $this->message = $data->headers['x-akismet-debug-help'];
    }
    // Another clue is if we receive the body text "Invalid API key."
    if ($this->body === 'Invalid API key.') {
      $this->isError = TRUE;
      $this->code = Client::AUTH_ERROR;
      $this->message = $this->body;
    }
  }

  public function __get($name) {
    return $this->{$name};
  }

  public function guid() {
    if (!empty($this->headers['x-akismet-guid'])) {
      return $this->headers['x-akismet-guid'];
    }
    return FALSE;
  }
}
