<?php

namespace Drupal\libraries_provider\Plugin\LibrarySource;

/**
 * Interface for Library sources.
 */
interface LibrarySourceInterface {

  /**
   * Fetch the available versions for a library.
   *
   * @return array
   *   An array of posible versions for the library.
   */
  public function getAvailableVersions(string $libraryId);

  /**
   * Returns the path to the resource inside the library.
   *
   * Removes the CDN parts and the minification of the file.
   *
   * @return string
   *   The clean path with a trailing slash.
   */
  public function getCanonicalPath(string $path);

  /**
   * Returns the path ready to use in a library definition.
   *
   * Given a canonical path, applies the necesary transformations to
   * return the version that corresponds with this source.
   *
   * @return string
   *   The complete path.
   */
  public function getPath(string $canonicalPath, array $library);

  /**
   * Wether this library can be retrieved from the source.
   */
  public function isAvailable(string $libraryId): bool;

}
