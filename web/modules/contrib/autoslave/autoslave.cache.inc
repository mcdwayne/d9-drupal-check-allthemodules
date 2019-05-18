<?php
/**
 * @file
 *
 * Cache class for gathering heuristics.
 */

class AutoslaveCache implements DrupalCacheInterface {
  private $backend = NULL;
  protected $bin;
  static private $buffer = array();

  function __destruct() {
    self::clearBuffer();
  }

  static function clearBuffer() {
    foreach (self::$buffer as $bin => $data) {
      list($cid, $wildcard) = $data;
      _cache_get_object($bin)->getBackend()->clear($cid, $wildcard);
    }
    self::$buffer = array();
  }

  function getBackend() {
    return $this->backend;
  }

  function clear($cid = NULL, $wildcard = FALSE) {
    $connection = Database::getConnection();
    $depth = $connection->transactionDepth();
    if ($depth == 0) {
      return $this->backend->clear($cid, $wildcard);
    }
    else {
      self::$buffer[$this->bin] = array($cid, $wildcard);
    }
  }

  function isEmpty() {
    return $this->backend->isEmpty();
  }

  function set($cid, $data, $expire = CACHE_PERMANENT) {
    return $this->backend->set($cid, $data, $expire);
  }

  function get($cid) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids);
    return reset($cache);
  }

  /**
   * Constructor.
   * Initialize bin and prefetch data
   */
  function __construct($bin) {
    $this->bin = $bin;

    // Let our parent do his work
    $class = variable_get('autoslave_cache_class_' . $bin);
    if (!isset($class)) {
      $class = variable_get('autoslave_cache_default_class', 'DrupalDatabaseCache');
    }
    $this->backend = new $class($bin);
  }

  /**
   * Aggregate cache data from prefetch and requested
   */
  function getMultiple(&$cids) {
    return $this->backend->getMultiple($cids);
  }
}

