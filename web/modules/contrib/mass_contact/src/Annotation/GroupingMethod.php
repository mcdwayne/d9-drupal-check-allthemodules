<?php

namespace Drupal\mass_contact\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a mass contact grouping method plugin.
 *
 * @Annotation
 */
class GroupingMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable title.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
