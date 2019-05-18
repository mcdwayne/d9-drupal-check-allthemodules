<?php
namespace Drupal\cloudwords;

/**
 * Exception thrown when a call to the Cloudwords API returns an exception.
 *
 * @author Douglas Kim <doug@cloudwords.com>
 * @since 1.0
 */
class CloudwordsApiException extends Exception {

  const API_EXCEPTION = 'api_exception';
  const UNSUPPORTED_CONTENT_TYPE_EXCEPTION = 'unsupported_content_type_exception';
  const REQUEST_EXCEPTION = 'request_exception';
  const DEPENDENCY_EXCEPTION = 'dependency_exception';

  protected $exception_type;

  protected $http_status_code;

  protected $request_type;

  protected $request_url;

  protected $error_message;

  protected $content_type;

  public function __construct($exception_type, $params) {
    $this->exception_type = $exception_type;
    if ($exception_type == self::API_EXCEPTION) {
      $this->http_status_code = $params['http_status_code'];
      $this->request_type = $params['request_type'];
      $this->request_url = $params['request_url'];
      $this->error_message = $params['error_message'];
    }
    elseif ($exception_type == self::UNSUPPORTED_CONTENT_TYPE_EXCEPTION) {
      $this->content_type = $params['content_type'];
    }
    elseif ($exception_type == self::REQUEST_EXCEPTION) {
      $this->error_message = $params['error_message'];
    }
    elseif ($exception_type == self::DEPENDENCY_EXCEPTION) {
      $this->error_message = $params['error_message'];
    }
  }

  public function getExceptionType() {
    return $this->exception_type;
  }

  public function getHttpStatusCode() {
    return $this->http_status_code;
  }

  public function getRequestType() {
    return $this->request_type;
  }

  public function getRequestUrl() {
    return $this->request_url;
  }

  public function getErrorMessage() {
    return $this->error_message;
  }

  public function getContentType() {
    return $this->content_type;
  }

  public function __toString() {
    if ($this->exception_type == self::API_EXCEPTION) {
      return "Received HTTP status code " . $this->http_status_code . " from " . $this->request_type . " request at " . $this->request_url . "\n" .
             "Error: " . $this->error_message . "\n";
    }
    elseif ($this->exception_type == self::UNSUPPORTED_CONTENT_TYPE_EXCEPTION) {
      return "Unsupported content type '" . $this->content_type . "'\n";
    }
    elseif ($this->exception_type == self::REQUEST_EXCEPTION) {
      return "Malformed request : " . $this->error_message . "\n";
    }
    elseif ($this->exception_type == self::DEPENDENCY_EXCEPTION) {
      return $this->error_message . "\n";
    }
  }

}
