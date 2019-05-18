<?php
/**
 * @file
 * Contains Drupal\block_render\Libraries\Libraries.
 */

namespace Drupal\block_render\Libraries;

use Drupal\block_render\Immutable;
use Drupal\block_render\Library\LibraryInterface;

/**
 * A set of libraries.
 */
final class Libraries extends Immutable implements LibrariesInterface {

  /**
   * Libraries.
   *
   * @var array
   */
  protected $libraries;

  /**
   * Create the Asset Response object.
   *
   * @param array $libraries
   *   An array of Drupal\block_render\Library\Library.
   */
  public function __construct(array $libraries = array()) {
    $this->libraries = array();
    foreach ($libraries as $library) {
      $this->addLibrary($library);
    }
  }

  /**
   * Adds a library.
   *
   * @param \Drupal\block_render\Library\LibraryInterface $library
   *   Signle Library.
   *
   * @return \Drupal\block_render\Libraries\Libraries
   *   Return the asset response object.
   */
  public function addLibrary(LibraryInterface $library) {
    $this->libraries[] = $library;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return $this->libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->libraries);
  }

}
