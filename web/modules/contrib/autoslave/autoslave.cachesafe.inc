<?php
/**
 * @file
 *
 * Cache class for gathering heuristics.
 */

class AutoslaveCacheSafe implements DrupalCacheInterface {
  private $backends = array();
  protected $bin;


  /**
   * Constructor.
   * Initialize bin and prefetch data
   */
  function __construct($bin) {
    $this->bin = $bin;

    // Let our parent do his work
    $classes = variable_get('autoslave_safe_cache_class_' . $bin);
    if (!isset($classes)) {
      $classes = variable_get('autoslave_safe_cache_default_class', array('DrupalDatabaseCache'));
    }
    if (!is_array($classes)) {
      $classes = array($classes);
    }
    foreach ($classes as $class) {
      $this->backends[] = new $class($bin);
    }
  }

  /**
   * Implements DrupalCacheInterface::clear().
   */
  function clear($cid = NULL, $wildcard = FALSE) {
    foreach ($this->backends as $backend) {
      $backend->clear($cid, $wildcard);
    }
  }

  /**
   * Implements DrupalCacheInterface::get().
   */
  public function get($cid) {
    foreach ($this->backends as $index => $backend) {
      if (($return = $backend->get($cid)) !== FALSE) {
        return $return;
      }
    }

    return FALSE;
  }

  /**
   * Implements DrupalCacheInterface::getMultiple().
   */
  public function getMultiple(&$cids) {
    $return = array();

    foreach ($this->backends as $index => $backend) {
      $items = $backend->getMultiple($cids);

      // Append the values to the previously retrieved ones.
      $return += $items;

      if (empty($cids)) {
        // No need to go further if we don't have any cid to fetch left.
        break;
      }
    }
    return $return;
  }

  /**
   * Implements DrupalCacheInterface::set().
   */
  public function set($cid, $data, $expire = CACHE_PERMANENT) {
    foreach ($this->backends as $backend) {
      $backend->set($cid, $data, $expire);
    }
  }

  /**
   * Implements DrupalCacheInterface::isEmpty().
   */
  public function isEmpty() {
    foreach ($this->backends as $backend) {
      if (!$backend->isEmpty()) {
        return FALSE;
      }
    }

    return TRUE;
  }

}

