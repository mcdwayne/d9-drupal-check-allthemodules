<?php
/**
 * @file
 * Contains \Drupal\widget_block\Backend\WidgetBlockBackendInterface.
 */

namespace Drupal\widget_block\Backend;

use Drupal\Core\Language\LanguageInterface;
use Drupal\widget_block\Entity\WidgetBlockConfigInterface;

/**
 * Interface which describes the Widget Block backend.
 */
interface WidgetBlockBackendInterface {

  /**
   * Invalidate the widget block markup for specified configuration.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which an invalidation should be performed.
   *
   * @return bool
   *   TRUE if operation was successful, otherwise FALSE.
   */
  public function invalidate(WidgetBlockConfigInterface $config, LanguageInterface $language);

  /**
   * Refresh the widget block markup for specified configuration.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which a refresh should be performed.
   * @param boolean $forced
   *   A flag which indicates whether refresh should be forced.
   *
   * @return bool
   *   TRUE if operation was successful, otherwise FALSE.
   */
  public function refresh(WidgetBlockConfigInterface $config, LanguageInterface $language, $forced = FALSE);

  /**
   * Get the widget block markup for specified configuration.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which the widget block will be resolved.
   *
   * @return \Drupal\widget_block\Renderable\WidgetMarkupInterface|null
   *   An instance of WidgetMarkupInterface if available, otherwise null.
   */
  public function getMarkup(WidgetBlockConfigInterface $config, LanguageInterface $language);

}
