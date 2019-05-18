<?php

namespace Drupal\entity_theme_suggestions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for EntityThemeSuggestions.
 *
 * @Annotation
 */
class EntityThemeSuggestions extends Plugin {

  /**
   * The entity type to define suggestions for.
   *
   * @var string
   */
  public $entityType;

  /**
   * The priority of this alter.
   *
   * @var int
   */
  public $priority = 1;

}
