<?php

namespace Drupal\automatic_updates\ReadinessChecker;

/**
 * Base class for filesystem checkers.
 */
abstract class Filesystem implements ReadinessCheckerInterface {

  /**
   * The root file path.
   *
   * @var string
   */
  protected $rootPath;

  /**
   * The vendor file path.
   *
   * @var string
   */
  protected $vendorPath;

  /**
   * The drupal finder service.
   *
   * @var \DrupalFinder\DrupalFinder
   */
  protected $drupalFinder;

  /**
   * {@inheritdoc}
   */
  public function run() {
    if (!$this->getRootPath() || !$this->exists($this->getRootPath() . '/core/core.api.php')) {
      return [$this->t('The web root could not be located.')];
    }

    return $this->doCheck();
  }

  /**
   * Perform checks.
   *
   * @return array
   *   An array of translatable strings if any checks fail.
   */
  abstract protected function doCheck();

  /**
   * Get the drupal finder service.
   *
   * @return \DrupalFinder\DrupalFinder
   *   Get drupal finder service.
   */
  protected function getDrupalFinder() {
    if (!$this->drupalFinder) {
      $this->drupalFinder = \Drupal::service('automatic_updates.drupal_finder');
    }
    return $this->drupalFinder;
  }

  /**
   * Get the root file path.
   *
   * @return string
   *   The root file path.
   */
  protected function getRootPath() {
    if (!$this->rootPath && $this->getDrupalFinder()->locateRoot(getcwd())) {
      $this->rootPath = $this->getDrupalFinder()->getDrupalRoot();
    }
    return $this->rootPath;
  }

  /**
   * Get the vendor file path.
   *
   * @return string
   *   The vendor file path.
   */
  protected function getVendorPath() {
    if (!$this->vendorPath && $this->getDrupalFinder()->locateRoot(getcwd())) {
      $this->vendorPath = $this->getDrupalFinder()->getVendorDir();
    }
    return $this->vendorPath;
  }

  /**
   * Determine if the root and vendor file system are the same logical disk.
   *
   * @param string $root
   *   Root file path.
   * @param string $vendor
   *   Vendor file path.
   *
   * @return bool
   *   TRUE if same file system, FALSE otherwise.
   */
  protected function areSameLogicalDisk($root, $vendor) {
    $root_statistics = stat($root);
    $vendor_statistics = stat($vendor);
    return $root_statistics && $vendor_statistics && $root_statistics['dev'] === $vendor_statistics['dev'];
  }

  /**
   * Checks whether a file or directory exists.
   *
   * @param string $file
   *   The file path to test.
   *
   * @return bool
   *   TRUE if the file exists, otherwise FALSE.
   */
  protected function exists($file) {
    return file_exists($file);
  }

}
