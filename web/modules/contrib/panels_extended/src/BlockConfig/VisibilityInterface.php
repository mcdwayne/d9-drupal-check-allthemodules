<?php

namespace Drupal\panels_extended\BlockConfig;

/**
 * Provides an interface for determining if a block should be visible.
 */
interface VisibilityInterface {

  /**
   * Determines if a block should be visible.
   *
   * @return bool
   *   TRUE when visible, FALSE otherwise.
   */
  public function isVisible();

  /**
   * Provides the reason when the block is not visible.
   *
   * @return string
   *   The reason when the block isn't visible.
   */
  public function getNotVisibleReason();

}
