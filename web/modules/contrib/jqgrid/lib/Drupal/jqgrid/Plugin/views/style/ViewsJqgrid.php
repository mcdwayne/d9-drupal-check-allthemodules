<?php

/**
 * @file
 * Definition of Drupal\jqgrid\Plugin\views\style\ViewsJqgrid.
 */

namespace Drupal\jqgrid\Plugin\views\style;

use Drupal\views\Annotation\ViewsStyle;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "jqgrid",
 *   title = @Translation("jQuery jqgrid"),
 *   help = @Translation("jQuery jqgrid."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class ViewsJqgrid extends StylePluginBase {
  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowPlugin
   */
  protected $usesRowPlugin = TRUE;

  /**
  * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowClass.
  */
  protected $usesRowClass = TRUE;

  /**
  * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::groupingTheme.
  */
  // protected $groupingTheme = 'views_view_grouping';

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  public function preRender($values) {
  }

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase\StylePluginBase::render().
   */
  public function render() {
    $output = '';
    return $output;
  }
}
