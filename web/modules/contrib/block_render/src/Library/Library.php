<?php
/**
 * @file
 * Contains Drupal\block_render\Library\Library.
 */

namespace Drupal\block_render\Library;

use Drupal\block_render\Immutable;

/**
 * Single Library.
 */
final class Library extends Immutable implements LibraryInterface {

  /**
   * Name of the library.
   *
   * @var string
   */
  protected $name;

  /**
   * Version number string.
   *
   * @var string
   */
  protected $version;

  /**
   * Construct the Library.
   *
   * @param string $name
   *   Name of the library.
   * @param string $version
   *   A version number string.
   */
  public function __construct($name, $version = '') {
    $this->name = $name;
    $this->version = $version;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->version;
  }

}
