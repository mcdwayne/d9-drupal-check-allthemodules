<?php

/**
 * @file
 * Definition of Drupal\views_responsive_grid\Plugin\views\style\ResponsiveGrid.
 */

namespace Drupal\views_responsive_grid\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Style plugin to render each item in a responsive grid "cell".
 *
 * @ingroup views_style_plugins
 *
 * @Plugin(
 *   id = "responsive_grid",
 *   module = "views_responsive_grid",
 *   title = @Translation("Responsive grid"),
 *   help = @Translation("Displays rows in a responsive grid."),
 *   theme = "views_view_responsive_grid",
 *   theme_file = "views_responsive_grid.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ResponsiveGrid extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options
   */
  function defineOptions() {
    $options = parent::defineOptions();
    $options['columns'] = array('default' => '4');
    $options['automatic_width'] = array('default' => TRUE, 'bool' => TRUE);
    $options['alignment'] = array('default' => 'horizontal');
    $options['col_class'] = array('default' => '');
    $options['default_col_class'] = array('default' => TRUE, 'bool' => TRUE);
    $options['col_class_special'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Build the options form.
   */
  function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (!empty($form['uses_fields'])) {
      $form['uses_fields']['#weight'] = -10;
    }
    $form['default_row_class']['#description'] = t('Add the default row classes like views-row, row-1 and clearfix to the output. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.');
    $form['row_class_special']['#description'] = t('Add css classes to the first and last rows, as well as odd/even classes for striping.');
    $form['columns'] = array(
      '#type' => 'number',
      '#title' => t('Number of columns'),
      '#default_value' => $this->options['columns'],
      '#required' => TRUE,
      '#min' => 0,
      '#weight' => -9,
    );
    $form['automatic_width'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatic width'),
      '#description' => t('The width of each column will be calculated automatically based on the number of columns entered. If additional classes are entered or a theme injects additional classes based on a grid system, disabling this option may prove beneficial.'),
      '#default_value' => $this->options['automatic_width'],
      '#weight' => -8,
    );
    $form['alignment'] = array(
      '#type' => 'radios',
      '#title' => t('Alignment'),
      '#options' => array('horizontal' => t('Horizontal'), 'vertical' => t('Vertical')),
      '#default_value' => $this->options['alignment'],
      '#description' => t('Horizontal alignment will place items starting in the upper left and moving right. Vertical alignment will place items starting in the upper left and moving down.'),
      '#weight' => -7,
    );
    $form['col_class'] = array(
      '#title' => t('Column class'),
      '#description' => t('The class to provide on each column.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['col_class'],
    );
    if ($this->usesFields()) {
      $form['col_class']['#description'] .= ' ' . t('You may use field tokens from as per the "Replacement patterns" used in "Rewrite the output of this field" for all fields.');
    }
    $form['default_col_class'] = array(
      '#title' => t('Add views column classes'),
      '#description' => t('Add the default column classes like views-col, col-1 and clearfix to the output. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['default_col_class'],
    );
    $form['col_class_special'] = array(
      '#title' => t('Add striping (odd/even), first/last column classes'),
      '#description' => t('Add css classes to the first and last columns, as well as odd/even classes for striping.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['col_class_special'],
    );
  }

}
