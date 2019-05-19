<?php
/**
 * @file
 * Contains \Drupal\widget_block\Plugin\Block\WidgetBlockInterface.
 */

namespace Drupal\widget_block\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Interface which describes a widget block.
 */
interface WidgetBlockInterface extends BlockPluginInterface {

  /**
   * Get the configuration entity.
   *
   * @return \Drupal\widget_block\Entity\WidgetBlockConfigInterface|NULL
   *   An instance of WidgetBlockConfigInterface if available, otherwise NULL.
   */
  public function getConfigEntity();

  /**
   * Invalida the widget block.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which invalidation should be performed.
   *
   * @return bool
   *   TRUE if operation was successful, otherwise FALSE.
   */
  public function invalidate(LanguageInterface $language);

  /**
   * Refresh the widget block.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which refresh should be performed.
   * @param bool $forced
   *   Flag which indicates whether refresh should be performed
   *   even if the markup is already up to date.
   *
   * @return bool
   *   TRUE if operation was successful, otherwise FALSE.
   */
  public function refresh(LanguageInterface $language, $forced = FALSE);

}
