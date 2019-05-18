<?php

namespace Drupal\bigvideo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining BigVideo Page entities.
 */
interface BigvideoPageInterface extends ConfigEntityInterface {

  const DEFAULT_SELECTOR = 'body';

  /**
   * Get source identifier.
   *
   * @return int|null
   *   Source identifier.
   */
  public function getSource();

  /**
   * Set source identifier.
   *
   * @param int $source
   *   Source identifier.
   *
   * @return $this
   */
  public function setSource($source);

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

  /**
   * Get page selector.
   *
   * @return string
   *   Page selector.
   */
  public function getSelector();

  /**
   * Set page selector.
   *
   * @param string $selector
   *   New page selector.
   *
   * @return $this
   */
  public function setSelector($selector);

}
