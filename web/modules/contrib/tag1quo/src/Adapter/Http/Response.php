<?php

namespace Drupal\tag1quo\Adapter\Http;

/**
 * Class Response.
 *
 * @internal This class is subject to change.
 */
class Response {

  protected $content;
  protected $headers;
  protected $statusCode;

  public function __construct($content = '', $statusCode = 200, array $headers = array()) {
    $this->statusCode = (int) $statusCode;
    $this->setHeaders($headers);

    // Decode compressed content.
    if ($encoding = $this->getEncoding()) {
      if (in_array('gzip', $encoding) || in_array('deflate', $encoding)) {
        $decompressed = gzdecode($content);
        if ($decompressed !== FALSE) {
          $content = $decompressed;
        }
      }
    }

    $this->setContent($content);
  }

  /**
   * @return string
   */
  public function getContent() {
    return $this->content;
  }

  public function getEncoding() {
    return $this->getHeader('Content-Encoding');
  }

  public function getHeader($name, $default = array()) {
    $name = strtolower($name);
    return isset($this->headers[$name]) ? $this->headers[$name] : $default;
  }

  /**
   * @return array
   */
  public function getHeaders() {
    return $this->headers;
  }

  public function getMessage() {
    return '';
  }

  /**
   * @return int
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  public function isSuccessful() {
    $statusCode = $this->getStatusCode();
    return ($statusCode >= 200 && $statusCode < 300) || $statusCode === 301 || $statusCode === 302;
  }

  public function setContent($content) {
    $this->content = (string) $content;
    return $this;
  }

  public function setHeaders(array $headers = array()) {
    $this->headers = array();
    foreach ($headers as $name => $value) {
      if (!is_array($value)) {
        $value = [$value];
      }

      $value = array_map(function ($value) {
        return trim($value, " \t");
      }, $value);

      $this->headers[strtolower($name)] = $value;
    }
    return $this;
  }

  public function toArray() {
    return get_object_vars($this);
  }

}
