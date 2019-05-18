<?php

namespace Drupal\entity_theme_suggestions\Plugin\Definition;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for entity theme suggestions.
 *
 * @package Drupal\entity_theme_suggestions
 */
class EntityThemeSuggestionsBase extends PluginBase implements EntityThemeSuggestionsInterface {

  /**
   * {@inheritdoc}
   */
  public function alterSuggestions(array &$suggestions, array $variables, $hook) {
    $entity = $variables['elements']['#' . $hook];
    $view_mode = $variables['elements']['#view_mode'];
    if (!empty($entity)) {
      // Set the view mode to full in case we are on the default one.
      if ($view_mode == 'default') {
        $view_mode = 'full';
      }
      // Sanitize the view mode.
      $sanitized_view_mode = strtr($view_mode, '.', '_');

      // Build the template suggestions.
      $suggestions[] = $entity->getEntityTypeId();
      $suggestions[] = $entity->getEntityTypeId() . '__' . $sanitized_view_mode;
      $suggestions[] = $entity->getEntityTypeId() . '__' . $entity->bundle();
      $suggestions[] = $entity->getEntityTypeId() . '__' . $entity->bundle() . '__' . $sanitized_view_mode;
      $suggestions[] = $entity->getEntityTypeId() . '__' . $entity->id();
      $suggestions[] = $entity->getEntityTypeId() . '__' . $entity->id() . '__' . $sanitized_view_mode;
    }
  }

}
