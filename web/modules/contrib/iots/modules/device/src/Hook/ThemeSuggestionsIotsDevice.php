<?php

namespace Drupal\iots_device\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * Hook ThemeSuggestionsIotsDevice.
 */
class ThemeSuggestionsIotsDevice extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook($variables) {
    $suggestions = [];
    $entity = $variables['elements']['#iots_device'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

    $suggestions[] = 'iots_device__' . $sanitized_view_mode;
    $suggestions[] = 'iots_device__' . $entity->bundle();
    $suggestions[] = 'iots_device__' . $entity->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = 'iots_device__' . $entity->id();
    $suggestions[] = 'iots_device__' . $entity->id() . '__' . $sanitized_view_mode;
    return $suggestions;
  }

}
