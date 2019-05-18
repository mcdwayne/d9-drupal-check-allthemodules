<?php

/**
 * @file
 * Contains \Drupal\apiservices\UrlBuilder.
 */

namespace Drupal\apiservices;

/**
 * Builds a URL from a template.
 *
 * This class should not be used to validate a URL.
 */
class UrlBuilder {

  /**
   * The URL host (ex: '//www.example.com').
   *
   * @var string
   */
  protected $host;

  /**
   * The URL path (ex: '/path/to/resource').
   *
   * @var string
   */
  protected $path;

  /**
   * A list of URL query parameters. Each parameter value may be a single value
   * or an array of values.
   *
   * Examples:
   * @code
   *   [a => b, c => d] => ?a=b&c=d
   *   [a => [b, c], d => e] => ?a=b,c&d=e
   * @endcode
   *
   * @var array
   */
  protected $query = [];

  /**
   * A list of placeholders and replacements that can be used if a URL template
   * is given.
   *
   * @var array
   */
  protected $replacements = [];

  /**
   * The URL scheme (ex: 'https:').
   *
   * @var string
   */
  protected $scheme;

  /**
   * Constructs a UrlBuilder object.
   *
   * @param string $scheme
   *   The URL scheme.
   * @param string $host
   *   The URL host.
   * @param string $path
   *   (optional) The URL path.
   * @param array $query
   *   (optional) A list of URL query parameters and values.
   */
  public function __construct($scheme, $host, $path = '', $query = []) {
    $this->setScheme($scheme);
    $this->setHost($host);
    $this->setPath($path);
    foreach ($query as $parameter => $value) {
      $this->setQueryParameter($parameter, $value);
    }
  }

  /**
   * Encodes query parameter values and combines all parameters into a URL query
   * string.
   *
   * @return string
   *   The query string component of the URL.
   */
  protected function buildQuery() {
    $query = [];
    foreach($this->query as $parameter => $value) {
      // Drop the parameter if it has no value.
      if (empty($value)) {
        continue;
      }
      // Encode the value (using RFC 3986) and append to query list.
      $value = is_array($value) ? implode(',', array_map('rawurlencode', $value)) : rawurlencode($value);
      $query[] = $parameter . '=' . $value;
    }
    return implode('&', $query);
  }

  /**
   * Clears all stored placeholder and replacement values.
   *
   * @return $this
   */
  public function clearPlaceholders() {
    $this->replacements = [];
    return $this;
  }

  /**
   * Clears all stored query parameters.
   *
   * @return $this
   */
  public function clearQuery() {
    $this->query = [];
    return $this;
  }

  /**
   * Gets the URL path.
   *
   * @return string
   *   The path component of the URL.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Gets the replacement value for a given placeholder.
   *
   * @param string $placeholder
   *   The placeholder value in the URL.
   *
   * @return string
   *   The replacement value for the placeholder.
   */
  public function getPlaceholder($placeholder) {
    if (!isset($this->replacements[$placeholder])) {
      throw new \OutOfBoundsException('Invalid URL placeholder');
    }
    return $this->replacements[$placeholder];
  }

  /**
   * Gets all placeholders and associated replacement values.
   *
   * @return array
   *   A list of placeholders and replacements that would be used if a URL
   *   template was used.
   */
  public function getPlaceholders() {
    return $this->replacements;
  }

  /**
   * Gets the URL query string.
   *
   * @return string
   *   The query string portion of the URL, after encoding.
   */
  public function getQuery() {
    return $this->buildQuery();
  }

  /**
   * Gets the value of a query parameter.
   *
   * @param string $parameter
   *   The name of the query parameter.
   *
   * @return mixed
   *   A string, or list of strings, representing the parameter value.
   *
   * @see UrlBuilder::hasQueryParameter()
   */
  public function getQueryParameter($parameter) {
    if (!isset($this->query[$parameter])) {
      throw new \OutOfBoundsException('Invalid query parameter');
    }
    return $this->query[$parameter];
  }

  /**
   * Gets a URL string with placeholders.
   *
   * @return string
   *   A partially-formed URL.
   *
   * @see UrlBuilder::getPlaceholders()
   */
  public function getRawUrl() {
    $url = $this->scheme . $this->host . $this->path;
    $query = $this->buildQuery();
    if (!empty($query)) {
      $url .= '?' . $query;
    }
    return $url;
  }

  /**
   * Gets a string representation of the URL.
   *
   * @return string
   *   A fully-formed URL.
   */
  public function getUrl() {
    $url = $this->getRawUrl();
    // There may also be placeholders in the URL host that need replacing.
    $placeholders = array_keys($this->replacements);
    $replacements = array_map('rawurlencode', array_values($this->replacements));
    return str_replace($placeholders, $replacements, $url);
  }

  /**
   * Determines if a value for the specified placeholder has been set.
   *
   * @param string $placeholder
   *   The name of the placeholder.
   *
   * @return bool
   *   If the placeholder has a value, TRUE, otherwise FALSE.
   */
  public function hasPlaceholder($placeholder) {
    return isset($this->replacements[$placeholder]);
  }

  /**
   * Determines if the specified query parameter currently has a set value.
   *
   * @param string $parameter
   *   The query parameter name.
   *
   * @return bool
   *   If the query parameter exists, TRUE, otherwise FALSE.
   */
  public function hasQueryParameter($parameter) {
    return isset($this->query[$parameter]);
  }

  /**
   * Removes a query parameter from the URL.
   *
   * @param string $parameter
   *   The query parameter name.
   *
   * @return $this
   */
  public function removeQueryParameter($parameter) {
    unset($this->query[$parameter]);
    return $this;
  }

  /**
   * Sets the URL host.
   *
   * @param string $host
   *   The URL host (ex: '//www.example.com').
   *
   * @return $this
   */
  public function setHost($host) {
    $pos = strpos($host, '//');
    if ($pos === FALSE) {
      $host = '//' . $host;
    }
    else if ($pos != 0) {
      throw new \Exception('Invalid URL host');
    }
    if (strlen($host) <= 2) {
      throw new \InvalidArgumentException('Host cannot be empty');
    }
    $this->host = $host;
    return $this;
  }

  /**
   * Sets the URL path.
   *
   * @param string $path
   *   The URL path (ex: '/path/to/resource'). This path may also contain
   *   placeholders that should be replaced when generating the complete URL.
   *
   * @return $this
   *
   * @see UrlBuilder::setPlaceholder()
   */
  public function setPath($path) {
    $this->path = (string) $path;
    return $this;
  }

  /**
   * Sets the replacement value for a placeholder in the URL.
   *
   * @param string $placeholder
   *   The placeholder value in the URL.
   * @param string $replacement
   *   The replacement value for the placeholder.
   *
   * @return $this
   */
  public function setPlaceholder($placeholder, $replacement) {
    $this->replacements[$placeholder] = (string) $replacement;
    return $this;
  }

  /**
   * Sets a URL query parameter.
   *
   * @param string $parameter
   *   The query parameter.
   * @param string|array $value
   *   A string, or list of strings, containing parameter values. If a value is
   *   empty, the query parameter will also be removed from the URL.
   *
   * @return $this
   */
  public function setQueryParameter($parameter, $value) {
    if (empty($value)) {
      return $this->removeQueryParameter($parameter);
    }
    $this->query[$parameter] = $value;
    return $this;
  }

  /**
   * Sets the URL scheme.
   *
   * @param string $scheme
   *   The URL scheme (with or without a trailing ':').
   *
   * @return $this
   */
  public function setScheme($scheme) {
    if (empty($scheme)) {
      throw new \InvalidArgumentException('Scheme cannot be empty');
    }
    $pos = strpos($scheme, ':');
    if ($pos === FALSE) {
      $scheme .= ':';
    }
    else if ($pos != strlen($scheme) - 1) {
      // There can only be one colon, and it must be at the end of the scheme.
      throw new \Exception('Invalid URL scheme');
    }
    $this->scheme = $scheme;
    return $this;
  }

}
