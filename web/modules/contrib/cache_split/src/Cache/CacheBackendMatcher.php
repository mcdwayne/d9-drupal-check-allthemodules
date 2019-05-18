<?php
/**
 * @file
 * Contains \Drupal\cache_split\Cache\CacheBackendMatcher.
 */

namespace Drupal\cache_split\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides a matcher assoiciatin a Cache Backend with a cache id pattern.
 */
class CacheBackendMatcher {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $backend;

  /**
   * @var string
   */
  protected $include_regex = '';

  /**
   * @var string
   */
  protected $exclude_regex = '';

  /**
   * CacheBackendMatcher constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   * @param array $config
   *   The config may contain:
   *   - includes: array of patterns to use for including cids
   *   - excludes: array of patterns to use for excluding cids
   *
   *   If includes and excludes are empty, anything matches.
   */
  public function __construct(CacheBackendInterface $backend, array $config) {
    // Provide defaults for config.
    $config += [
      'includes' => [],
      'excludes' => [],
    ];

    $this->backend = $backend;

    // Generate regex patterns for the given config.
    $this->include_regex = $this->generateRegex($config['includes']);
    $this->exclude_regex = $this->generateRegex($config['excludes']);
  }

  /**
   * Checks if the matcher can be used as default matcher.
   *
   * @return bool
   */
  public function isFallback() {
    return $this->include_regex === '' && $this->exclude_regex === '';
  }

  /**
   * Check if the given Cache ID matches the pattern.
   *
   * @param string $cid
   *   Cache ID to check.
   *
   * @return bool
   */
  public function match($cid) {
    // If an include pattern is given and it does not match, it is no match.
    if ($this->include_regex !== '' && !preg_match($this->include_regex, $cid)) {
      return FALSE;
    }

    // If the exclude pattern matches, it does not match either.
    if ($this->exclude_regex !== '' && preg_match($this->exclude_regex, $cid)) {
      return FALSE;
    }

    // Otherwise there was no restriction and we simply pass a match.
    return TRUE;
  }

  /**
   * Filters the given list of cids.
   *
   * @param array $cids
   *   List of cache IDS.
   *
   * @return array
   */
  public function filter(array $cids) {
    return array_filter($cids, [$this, 'match']);
  }

  /**
   * Call method of the cache backend with given set of arguments.
   *
   * @param string $method
   *   Method to call on the cache backend.
   * @param array $args
   *   Variables to pass to the cache backend.
   *
   * @return mixed
   */
  public function call($method, $args = []) {
    return call_user_func_array([$this->getBackend(), $method], $args);
  }

  /**
   * Get the associated backend.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   */
  public function getBackend() {
    return $this->backend;
  }

  /**
   * Generate a regex from the pattern array.
   *
   * @param array $patterns
   *   List of cid patterns, maybe using wildcards `*`.
   *
   * @return string
   *   Regular expression to be used to match a cid.
   *
   * @see \Drupal\Core\Path\PathMatcher::matchPath()
   */
  protected function generateRegex(array $patterns) {
    if (empty($patterns)) {
      return '';
    }

    // Build a multiline string.
    $patterns = implode(PHP_EOL, $patterns);
    $to_replace = array(
      // Replace newlines with a logical 'or'.
      '/(\r\n?|\n)/',
      // Quote asterisks.
      '/\\\\\*/',
    );
    $replacements = array(
      '|',
      '.*',
    );
    $patterns_quoted = preg_quote($patterns, '/');
    $regex = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')$/';
    return $regex;
  }
}
