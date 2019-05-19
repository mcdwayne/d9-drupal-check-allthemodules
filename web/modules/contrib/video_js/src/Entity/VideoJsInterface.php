<?php

namespace Drupal\video_js\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining VideoJs Page entities.
 */
interface VideoJsInterface extends ContentEntityInterface {

  const TYPE_FILE = 'file';
  const TYPE_LINK = 'link';

  /**
   * Get path where page will be used.
   *
   * @return string
   *   Path string.
   */
  public function getPath();

  /**
   * Set path where page will be used.
   *
   * @param string $path
   *   Path string.
   *
   * @return $this
   */
  public function setPath($path);

}
