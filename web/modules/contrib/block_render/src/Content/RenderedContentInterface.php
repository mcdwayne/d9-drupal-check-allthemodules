<?php
/**
 * @file
 * Contains Drupal\block_render\Content\RenderedContentInterface.
 */

namespace Drupal\block_render\Content;

/**
 * Contains the rendered content.
 */
interface RenderedContentInterface extends \IteratorAggregate {

  /**
   * Gets the content.
   *
   * @return arrat
   *   Array of DDrupal\Component\Render\MarkupInterface objects.
   */
  public function getContent();

  /**
   * Determines if this is a single item.
   *
   * @return bool
   *   Whether a single item should be returned.
   */
  public function isSingle();

}
