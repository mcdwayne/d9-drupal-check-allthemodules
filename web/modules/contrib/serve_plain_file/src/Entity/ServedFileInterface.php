<?php

namespace Drupal\serve_plain_file\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Served File entity.
 */
interface ServedFileInterface extends ConfigEntityInterface {

  /**
   * Gets the path to the file.
   *
   * @return string
   */
  public function getPath();

  /**
   * Gets the link to the file.
   *
   * @return \Drupal\Core\Link
   */
  public function getLinkToFile();

  /**
   * Gets the file content.
   *
   * @return string
   */
  public function getContent();

  /**
   * Gets the first line of the content.
   *
   * @return string
   */
  public function getContentHead();

  /**
   * Gets the cache max age setting for the served file.
   *
   * @return int
   */
  public function getFileMaxAge();

  /**
   * Gets the configured mime type which will be send in the response header.
   *
   * @return string
   */
  public function getMimeType();

  /**
   * A list of absolute urls to purge external caches.
   *
   * This includes the current path and optionally the original path when
   * the file path was changed.
   *
   * Use in conjunction with entity hooks to monitor changes & purge caches.
   *
   * @return string[]
   */
  public function getUrlsForCachePurging();

}
