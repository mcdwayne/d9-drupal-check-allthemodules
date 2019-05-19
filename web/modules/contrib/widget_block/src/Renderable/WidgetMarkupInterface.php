<?php
/**
 * @file
 * Contains \Drupal\widget_block\Renderable\WidgetMarkupInterface.
 */

namespace Drupal\widget_block\Renderable;

use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Interface which represents a Widget Block markup.
 */
interface WidgetMarkupInterface extends RenderableInterface, CacheableDependencyInterface {

  /**
   * Get the widget identifier.
   *
   * @return string
   *   Unique identifier of the widget.
   */
  public function id();

  /**
   * Get the widget include mode.
   *
   * @return integer
   *   The mode used for the widget.
   *
   * @see WidgetBlockConfigInterface::WIDGET_MODE_EMBED
   * @see WidgetBlockConfigInterface::WIDGET_MODE_SSI
   * @see WidgetBlockConfigInterface::WIDGET_MODE_SMART_SSI
   */
  public function getIncludeMode();

  /**
   * Get the language of the widget markup.
   *
   * @return string
   *   An ISO-639-6 language code.
   */
  public function getLangCode();

  /**
   * Get the markup content.
   *
   * @return \Drupal\Core\Render\MarkupInterface
   *   An instance of MarkupInterface.
   */
  public function getContent();

  /**
   * Get the assets related to the markup.
   *
   * @return array
   *   An associative array which contains the assets.
   */
  public function getAssets();

  /**
   * Determine whether the markup is cacheable.
   *
   * @return bool
   *   TRUE if the markup is cacheable, otherwise FALSE.
   */
  public function isCacheable();

  /**
   * Get a unix timestamp when the widget was created.
   *
   * @return integer
   *   A unix timestamp which represents the creation time.
   */
  public function getCreated();

  /**
   * Get a unix timestamp when the widget was modified.
   *
   * @return integer
   *   A unix timestamp which represents the last modification time.
   */
  public function getModified();

  /**
   * Get a unix timestamp when the widget was refreshed.
   *
   * @return integer
   *   A unix timestamp which represents the last refresh time.
   */
  public function getRefreshed();

}
