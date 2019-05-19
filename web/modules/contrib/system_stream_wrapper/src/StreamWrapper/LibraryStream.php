<?php

namespace Drupal\system_stream_wrapper\StreamWrapper;

use Drupal\system_stream_wrapper\Extension\LibraryDiscovery;

/**
 * Defines the read-only library:// stream wrapper for library files.
 */
class LibraryStream extends ExtensionStreamBase {

  /**
   * The path to the Drupal root.
   *
   * @var string
   */
  protected $drupalRoot;

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName() {
    $name = parent::getOwnerName();
    $library_discovery = new LibraryDiscovery($this->getDrupalRoot());
    $files = $library_discovery->scan(LibraryDiscovery::EXTENSION_TYPE);
    if (!isset($files[$name])) {
      throw new \InvalidArgumentException("Library $name does not exist");
    }

    return $name;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    $library_discovery = new LibraryDiscovery($this->getDrupalRoot());
    /** @var $files \Drupal\Core\Extension\Extension[] */
    $files = $library_discovery->scan(LibraryDiscovery::EXTENSION_TYPE);
    $name = $this->getOwnerName();
    return $files[$name]->getPathname();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Library files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Local files stored under the libraries directory.');
  }

  /**
   * Return the path to the Drupal root.
   *
   * Since stream wrappers don't allow us to pass in __construct() parameters,
   * we have to fall back to \Drupal.
   *
   * @return string
   */
  protected function getDrupalRoot() {
    if (!isset($this->drupalRoot)) {
      $this->drupalRoot = \Drupal::root();
    }
    return $this->drupalRoot;
  }

  /**
   * Set the path to the Drupal root.
   *
   * @param string $drupalRoot
   *   The absolute path to the root of the Drupal installation.
   */
  public function setDrupalRoot($drupalRoot) {
    $this->drupalRoot = $drupalRoot;
  }

}
