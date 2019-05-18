<?php

namespace Drupal\contest;

/**
 * Cache contest data.
 */
class ContestCache {
  const CID = 'contest-';
  const MAX = 255;

  /**
   * Flush the cache table.
   *
   * @return Drupal\Core\Database\Query\DeleteQuery
   *   A new DeleteQuery object for this connection.
   */
  public static function flushCache() {
    return ContestStorage::flushCache(self::CID . '%');
  }

  /**
   * Build and return a cache ID and resoectuve cached data.
   *
   * @param string|mixed $seeds
   *   Data used to build a unique cache ID.
   *
   * @return array
   *   A two element ordered array of: cache ID, cached data.
   */
  public static function get($seeds = []) {
    $cid = FALSE;
    $seeds = (array) $seeds;

    if (!\Drupal::currentUser()->hasPermission('administer contests')) {
      $cid = self::cid($seeds);

      $cache = $cid ? \Drupal::cache()->get($cid) : FALSE;

      if ($cache !== FALSE) {
        return [$cid, $cache->data];
      }
    }
    return [$cid, FALSE];
  }

  /**
   * Set the cache and return the data.
   *
   * @param string $cid
   *   The cache ID.
   * @param mixed $data
   *   The cached data.
   *
   * @return mixed
   *   The submitted, (and cached) data.
   */
  public static function set($cid, $data) {
    if (!\Drupal::currentUser()->hasPermission('administer contests') && !empty($cid)) {
      \Drupal::cache()->set($cid, $data);
    }
    return $data;
  }

  /**
   * Generate a cache ID.
   *
   * @param string|array $args
   *   The string(s) to build the cache ID from.
   *
   * @return string
   *   A unique cache ID.
   */
  protected static function cid($args = '') {
    $args = (array) $args;
    $cid = '';

    foreach ($args as $arg) {
      $cid .= (is_array($arg) || is_object($arg)) ? self::cid($arg) : "-$arg";
    }
    return ($cid && strlen($cid) < self::MAX) ? self::format($cid) : self::CID . md5($cid);
  }

  /**
   * Convert the provided string to a lowercase stroke delimited string.
   *
   * @param string $txt
   *   The string to convert.
   *
   * @return string
   *   A lowercase stroke delimited string.
   */
  protected static function format($txt) {
    return preg_replace(['/[^a-z0-9]+/', '/^-+|-+$/'], ['-', ''], strtolower($txt));
  }

}
