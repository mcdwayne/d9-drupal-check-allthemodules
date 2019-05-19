<?php

namespace Drupal\npm\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines node package manager executable annotation object.
 *
 * @Annotation
 */
class NpmExecutable extends Plugin {

  /**
   * The plugin ID.
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
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The weight of the plugin for precedence calculation.
   *
   * Lowest weight = highest priority.
   *
   * @var integer
   */
  public $weight;

}
