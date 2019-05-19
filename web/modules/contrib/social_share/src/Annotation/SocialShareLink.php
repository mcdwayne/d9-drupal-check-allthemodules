<?php

namespace Drupal\social_share\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation class for social share links.
 *
 * @Annotation
 */
class SocialShareLink extends Plugin {

  /**
   * The machine-name of the plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The category under which the plugin should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * Defines the used context of the action plugin.
   *
   * Array keys are the names of the contexts and values context definitions.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context = [];

}
