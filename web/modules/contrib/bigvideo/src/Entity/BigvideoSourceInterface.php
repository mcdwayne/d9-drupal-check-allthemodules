<?php

namespace Drupal\bigvideo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining BigVideo Source entities.
 */
interface BigvideoSourceInterface extends ConfigEntityInterface {
  const TYPE_FILE = 'file';
  const TYPE_LINK = 'link';

  /**
   * Get source type.
   *
   * @return string
   *   Source type.
   */
  public function getType();

  /**
   * Get path or file identifier for MP4.
   *
   * @return int|string
   *   Path or identifier.
   */
  public function getMp4();

  /**
   * Set identifier or path for MP4.
   *
   * @param string|int $mp4
   *   Identifier or path for MP4.
   *
   * @return $this
   */
  public function setMp4($mp4);

  /**
   * Get path or file identifier for WebM.
   *
   * @return int|string
   *   Path or identifier.
   */
  public function getWebM();

  /**
   * Set identifier or path for WebM.
   *
   * @param string|int $webm
   *   Identifier or path for WebM.
   *
   * @return $this
   */
  public function setWebM($webm);

  /**
   * Create array of source links.
   *
   * @return array
   *   Links to sources.
   */
  public function createVideoLinks();

}
