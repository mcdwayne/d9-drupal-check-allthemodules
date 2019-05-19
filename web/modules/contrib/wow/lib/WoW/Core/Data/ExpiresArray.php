<?php

/**
 * Definition of ExpiresArray.
 */

namespace WoW\Core\Data;

use ArrayAccess;

/**
 * Provides a caching wrapper to be used in place of expires array structure.
 *
 * Note: this class is designed for DataService internal use only.
 */
class ExpiresArray implements ArrayAccess {

  /**
   * An array of languages to persist at the end of the request.
   *
   * @var array
   */
  protected $languagesToPersist = array();

  /**
   * Storage for the data itself.
   *
   * @var array
   */
  protected $storage;

  /**
   * Constructs an ExpiresArray object.
   *
   * @param array $expires
   *   An array of expirations date keyed by entity types.
   */
  public function __construct(array $expires = array()) {
    $this->storage = $expires;
  }

  /**
   * Implements ArrayAccess::offsetExists().
   */
  public function offsetExists($language) {
    return $this->offsetGet($language) !== NULL;
  }

  /**
   * Implements ArrayAccess::offsetGet().
   */
  public function &offsetGet($language) {
    // If a DataService is asking for a language, it is likely to set a time
    // stamp. So the language is marked as 'persist'.
    $this->languagesToPersist[$language] = TRUE;
    return $this->storage[$language];
  }

  /**
   * Implements ArrayAccess::offsetSet().
   */
  public function offsetSet($language, $value) {
    $this->storage[$language] = $value;
  }

  /**
   * Implements ArrayAccess::offsetUnset().
   */
  public function offsetUnset($language) {
    unset($this->storage[$language]);
    unset($this->languagesToPersist[$language]);
  }

  /**
   * Destructs the ExpiresArray object.
   */
  public function __destruct() {
    foreach ($this->languagesToPersist as $language => $persist) {
      if ($persist) {
        // For each modified cache control value, updates the expires value of
        // the service.
        db_update('wow_services')
          ->fields(array('expires' => serialize($this->storage[$language])))
          ->condition('language', $language)
          ->execute();
      }
    }
  }

}
