<?php

namespace Drupal\phones_contact\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * Hook Cron.
 */
class ThemeSuggestionsPhonesContact extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(array $variables) {
    $suggestions = [];
    $entity = $variables['elements']['#phones_contact'];
    $sanitized_view_mode = strtr($variables['elements']['#view_mode'], '.', '_');

    $suggestions[] = 'phones_contact__' . $sanitized_view_mode;
    $suggestions[] = 'phones_contact__' . $entity->bundle();
    $suggestions[] = 'phones_contact__' . $entity->bundle() . '__' . $sanitized_view_mode;
    $suggestions[] = 'phones_contact__' . $entity->id();
    $suggestions[] = 'phones_contact__' . $entity->id() . '__' . $sanitized_view_mode;
    return $suggestions;
  }

}
