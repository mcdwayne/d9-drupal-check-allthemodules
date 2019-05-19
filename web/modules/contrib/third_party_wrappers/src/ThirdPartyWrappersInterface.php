<?php

namespace Drupal\third_party_wrappers;

/**
 * Provides an interface for common Third Party Wrappers functionality.
 */
interface ThirdPartyWrappersInterface {

  /**
   * Removes outdated copies of aggregated CSS and JS files.
   *
   * @param string $path
   *   The path to clean.
   * @param int $age
   *   The maximum age of files to keep.
   */
  public function cleanDirectory($path, $age);

  /**
   * Copies aggregated files into a separate folder to preserve them.
   *
   * @param string $template
   *   The loaded page data that is being processed and split.
   * @param string $type
   *   The type of files to copy. Can be either 'css' or 'js'.
   */
  public function copyFiles($template, $type);

  /**
   * Returns information about the system public files path.
   *
   * @return array
   *   An array containing both the un-escaped and escaped path.
   */
  public function getFilePaths();

  /**
   * Returns the maximum age setting.
   *
   * @return int
   *   The maximum age setting, in seconds.
   */
  public function getMaxAge();

  /**
   * Returns the split marker string.
   *
   * @return string
   *   The split marker string.
   */
  public function getSplitOn();

  /**
   * Returns the name of the directory where copied CSS/JS files are stored.
   *
   * @return string
   *   The storage directory name.
   */
  public function getDir();

  /**
   * Return the URI of the directory where copied CSS/JS files are stored.
   *
   * @return string
   *   The storage directory URI.
   */
  public function getUri();

}
