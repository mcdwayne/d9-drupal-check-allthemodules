<?php
/**
 * @file
 * Contains \Drupal\cache_split\CacheCacheBackendMatcherCollection.
 */

namespace Drupal\cache_split\Cache;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Collection for multiple cache backends.
 */
class CacheBackendMatcherCollection {

  /**
   * @var \Drupal\cache_split\Cache\CacheBackendMatcher[]
   */
  protected $matchers = [];

  /**
   * @var \Drupal\cache_split\Cache\CacheBackendMatcher[]
   */
  protected $fallbackMatcher;

  /**
   * Adds a CacheBackendMatcher to thecollection.
   *
   * @param \Drupal\cache_split\Cache\CacheBackendMatcher $matcher
   *   Matcher instance to process.
   */
  public function add(CacheBackendMatcher $matcher) {
    $this->matchers[] = $matcher;
  }

  /**
   * Get all matchers of this collection.
   *
   * @param bool $include_default
   *   When set to TRUE (default), the default matcher is appended to the list.
   *
   * @return \Drupal\cache_split\Cache\CacheBackendMatcher[]
   */
  public function getMatchers($include_default = TRUE) {
    if ($include_default) {
      return array_merge($this->matchers, [$this->fallbackMatcher]);
    }
    else {
      return $this->matchers;
    }
  }

  /**
   * @param \Drupal\cache_split\Cache\CacheBackendMatcher $matcher
   */
  public function setFallbackMatcher(CacheBackendMatcher $matcher) {
    $this->fallbackMatcher = $matcher;
  }

  /**
   * Sets a default cache backend for this collection.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   */
  public function setFallbackBackend(CacheBackendInterface $backend) {
    $this->setFallbackMatcher(new CacheBackendMatcher($backend, []));
  }

  /**
   * Retrieve the first matching matcher for the given Cache ID.
   *
   * @param string $cid
   *   Single cache ID to get the associated matcher for.
   *
   * @return \Drupal\cache_split\Cache\CacheBackendMatcher
   */
  protected function getMatcher($cid) {
    foreach ($this->matchers as $matcher) {
      if ($matcher->match($cid)) {
        return $matcher;
      }
    }
    // Fallback to default if one is set.
    if ($this->fallbackMatcher) {
      return $this->fallbackMatcher;
    }
    // Otherwise throw an Exception, as this is not intended.
    throw new \Exception(sprintf('No default matcher available for processing cache id "%s"', $cid));
  }

  /**
   * Call a cache backend method for a single Cache ID.
   * @param string $cid
   *   Single cache ID to call the method for.
   * @param string $method
   *   Method to call on the associated cache backend.
   * @param array $args
   *   Variables to pass to the method.
   *
   * @return mixed
   */
  public function callSingle($cid, $method, $args = []) {
    return $this->getMatcher($cid)->call($method, array_merge([$cid], $args));
  }

  /**
   * Call cache backend method on associated backend for each cache id.
   *
   * @param array $cids
   *   List of Cache IDS to call backend methods for.
   * @param string $method
   *   Method to call on the associated cache backend.
   * @param array $args
   *   Variables to pass to the method.
   *
   * @return array
   *   Might return a merged array of return values for each backend call.
   */
  public function callMultiple($cids, $method, $args = []) {
    return $this->callMultipleByRef($cids, $method, $args);
  }

  /**
   * Call backend method foreach Cache ID by reference.
   *
   * @param array $cids
   *   Cache IDs passed by reference. This list will have the cache ids removed
   *   that were processed successfully.
   * @param string $method
   *   Method to call on the associated cache backend.
   * @param array $args
   *   Variables to pass to the method.
   *
   * @return array
   *   Merged result of the backend call.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::getMultiple()
   */
  public function callMultipleByRef(&$cids, $method, $args = []) {
    $return = [];
    $rest = $cids;
    // Array to store cache ids that were not processed by the associated call.
    $unprocessed = [];
    foreach ($this->getMatchers() as $matcher) {
      // We only process the associated cache ids with the associated backend.
      $filtered = $matcher->filter($rest);
      // We make sure to pass the filtered array as reference, so we can retrieve
      // unprocessed items for CacheBackendInterface::getMultiple().
      $ret = $matcher->call($method, array_merge([&$filtered], $args));
      $unprocessed += $filtered;
      if (is_array($ret)) {
        $return += $ret;
      }

      // The rest will be processed by the next matcher.
      $rest = array_diff($rest, $filtered);
      if (empty($rest)) {
        break;
      }
    }
    // Cache IDs passed by reference update to the list of cache ids that were
    // not successfully processed.
    $cids = $unprocessed;
    return $return;
  }

  /**
   * Call backend method for list of items keyed by cache id.
   *
   * @param array $items
   *   List of data keyed by Cache ID.
   * @param string $method
   *   Method to call on the associated cache backend.
   * @param array $args
   *   Variables to pass to the associated cache backend.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::setMultiple()
   */
  public function callMultipleByKey($items, $method, $args = []) {
    $cids = array_keys($items);

    foreach ($this->getMatchers() as $matcher) {
      $filtered = $matcher->filter($cids);

      // Do not call the backend for empty set of ids.
      if (empty($filtered)) {
        continue;
      }

      // Filters the items by key, being the filtered ids.
      $filtered_items = array_intersect_key($items, array_flip($filtered));
      $matcher->call($method, array_merge([$filtered_items], $args));

      // Only process the rest of the cache ids.
      $cids = array_diff($cids, $filtered);

      // Do not process further if there are no ids anymore
      if (empty($cids)) {
        break;
      }
    }
  }

  /**
   * Call all associated cache backends with the same method.
   *
   * @param $method
   *   Method to call on the cache backends.
   * @param $args
   *   Variables to pass to the method.
   */
  public function callAll($method, $args = []) {
    foreach ($this->getMatchers() as $matcher) {
      $matcher->call($method, $args);
    }
  }
}
