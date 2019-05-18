<?php

namespace Drupal\merlinone;

/**
 * Provides the MerlinOne API.
 */
interface MerlinOneApiInterface {

  /**
   * Sets the Archive URL.
   *
   * @param string $url
   *   The archive base URL.
   */
  public function setArchiveUrl($url);

  /**
   * Gets the Archive URL.
   *
   * @return string
   *   The archive base URL.
   */
  public function getArchiveUrl();

  /**
   * Gets the MX embed URL.
   *
   * @return string
   *   The MX embed URL.
   */
  public function getMxUrl();

  /**
   * Fetches a remote file from the Merlin API.
   *
   * @param mixed $item
   *   Item information from the Merlin search.
   * @param string $directory
   *   Destination directory.
   *
   * @return \Drupal\file\FileInterface
   *   A managed file
   */
  public function createFileFromItem($item, $directory);

  /**
   * Get the SODA version from the API.
   *
   * @return string
   *   The SODA version string.
   */
  public function getSodaVersion();

  /**
   * Indicates if the SODA version allows downsampling instead of resampling.
   *
   * Versions supporting this will be 1.0.27 or greater.
   *
   * @return bool
   *   True if resampling is supported.
   */
  public function sodaAllowsResampling();

}
