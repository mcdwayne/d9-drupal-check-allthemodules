<?php

namespace Drupal\views_zurb_foundation;

use Drupal\Component\Utility\Html;
use Drupal\views\ViewExecutable;

/**
 * The primary class for the Views Bootstrap module.
 *
 * Provides many helper methods.
 *
 * @ingroup utility
 */
class ViewsZurbFoundation {

  /**
   * Returns the theme hook definition information.
   */
  public static function getThemeHooks() {
    $hooks['views_zurb_foundation_block_grid'] = [
      'preprocess functions' => [
        'template_preprocess_views_zurb_foundation_block_grid',
        'template_preprocess_views_view',
      ],
      'file' => 'views_zurb_foundation.theme.inc',
    ];

    return $hooks;
  }

  /**
   * Get unique element id.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A ViewExecutable object.
   *
   * @return string
   *   A unique id for an HTML element.
   */
  public static function getUniqueId(ViewExecutable $view) {
    $id = $view->storage->id() . '-' . $view->current_display;
    return Html::getUniqueId('views-zurb-foundation-' . $id);
  }

}
