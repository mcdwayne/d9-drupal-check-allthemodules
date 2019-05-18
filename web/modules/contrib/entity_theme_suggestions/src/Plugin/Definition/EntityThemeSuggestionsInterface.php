<?php

namespace Drupal\entity_theme_suggestions\Plugin\Definition;

/**
 * Interface for entity theme suggestions.
 *
 * @package Drupal\entity_theme_suggestions
 */
interface EntityThemeSuggestionsInterface {

  /**
   * Alters the suggestions for a given entity type.
   *
   * @param array $suggestions
   *   The suggestions array.
   * @param array $variables
   *   The variables.
   * @param string $hook
   *   The hook.
   *
   * @return array
   *   The altered suggestions array.
   */
  public function alterSuggestions(array &$suggestions, array $variables, $hook);

}
