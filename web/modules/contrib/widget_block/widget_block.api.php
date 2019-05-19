<?php
/**
 * @file
 * Hooks provided by the Widget Block module.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\widget_block\Entity\WidgetBlockConfigInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Purge all related resource which use the given Widget Block configuration.
 *
 * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
 *   The widget block configuration for which a purge is requested.
 * @param \Drupal\Core\LanguageInterface $language
 *   The language which the resources need to be purged.
 */
function hook_widget_block_invalidate(WidgetBlockConfigInterface $config, LanguageInterface $language) {
  // Provide customized invalidation logic here.
}

/**
 * @} End of "addtogroup hooks".
 */
