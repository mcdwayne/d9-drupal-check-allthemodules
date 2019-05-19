<?php

/**
 * @file
 * Contains Drupal\summoner\LibraryList.
 */

namespace Drupal\summoner;
use Drupal\summoner\Exception\LibraryNotFoundException;

/**
 * Simple container for a set of libraries to be able to use typehinting in the
 * controller class.
 */
class LibraryList extends \ArrayIterator {
  public function __construct($value) {
    $libraries = explode(',', $value);
    array_walk($libraries, function (&$lib) {
      $lib = str_replace('::', '/', $lib);
    });
    $missing = array();
    $libraryDiscovery = \Drupal::service('library.discovery');
    foreach ($libraries as $lib) {
      list($extension, $name) = explode('/', $lib);
      if (!$libraryDiscovery->getLibraryByName($extension, $name)) {
        $missing[] = $extension . '/' . $name;
      }
    }
    if (count($missing) > 0) {
      throw new LibraryNotFoundException($missing);
    }
    parent::__construct($libraries, 0);
  }

  /**
   * Generate an array of libraries used by the javascript components.
   * @return array
   */
  public function toState() {
    $state = array();
    foreach ($this as $lib) {
      $state[$lib] = TRUE;
    }
    return $state;
  }

  /**
   * Magic method to generate a library string.
   */
  function __toString() {
    return implode(',', $this->getArrayCopy());
  }
}