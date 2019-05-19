<?php

namespace Drupal\views_plain\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin for the plain view.
 *
 * @ViewsStyle(
 *   id = "plain_style",
 *   title = @Translation("Plain"),
 *   help = @Translation("Displays content without the wrappers."),
 *   theme = "plain_style",
 *   display_types = {"normal"}
 * )
 */
class PlainStyle extends StylePluginBase {

  /**
   * Specifies if the plugin uses row plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

}
