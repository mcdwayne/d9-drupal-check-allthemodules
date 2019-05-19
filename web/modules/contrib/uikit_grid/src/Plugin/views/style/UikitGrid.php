<?php

namespace Drupal\uikit_grid\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Component\Utility\Html;


/**
 * Style plugin to render a Uikit grid.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "uikit_grid",
 *   title = @Translation("Uikit Grid"),
 *   help = @Translation("Render a grid based on uikit"),
 *   theme = "views_view_uikit_grid",
 *   display_types = { "normal" }
 * )
 */
class UikitGrid extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['columns'] = ['default' => '4'];
    $options['col_class_custom'] = ['default' => ''];
    $options['gap_size'] = ['default' => ''];
    $options['divider'] = ['default' => FALSE];
    $options['match_height'] = ['default' => FALSE];
    $options['parralax'] = ['default' => FALSE];
    $options['parralax_speed'] = ['default' => '150'];


    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state)
  {
    parent::buildOptionsForm($form, $form_state);
    $form['columns'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of columns'),
      '#default_value' => $this->options['columns'],
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['gap_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap size'),
      '#options' => [
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
        'collapse' => $this->t('None'),
      ],
      '#default_value' => $this->options['gap_size'],
      '#description' => $this->t('See @link for more information about gap size.', [
        '@link' => 'https://getuikit.com/docs/grid#gutter-modifiers'
      ])
    ];

    $form['divider'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add dividers'),
      '#default_value' => $this->options['divider'],
      '#description' => $this->t('See @link for more information about dividers.', [
        '@link' => 'https://getuikit.com/docs/grid#divider-modifier'
      ])
    ];
    $form['match_height'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match height'),
      '#default_value' => $this->options['match_height'],
      '#description' => $this->t('See @link for more information about match height.', [
        '@link' => 'https://getuikit.com/docs/grid#match-height'
      ])
    ];
    $form['parralax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Parralax'),
      '#default_value' => $this->options['parralax'],
      '#description' => $this->t('See @link for more information about the parralax option.', [
        '@link' => 'https://getuikit.com/docs/grid-parallax'
      ])
    ];

    $form['parralax_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Parralax speed'),
      '#default_value' => $this->options['parralax_speed'],
      '#description' => $this->t('See @link for more information about the parralax option.', [
        '@link' => 'https://getuikit.com/docs/grid-parallax'
      ]),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[parralax]"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $form['col_class_custom'] = [
      '#title' => $this->t('Custom column class'),
      '#description' => $this->t('Additional classes to provide on each column. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['col_class_custom'],
    ];
    if ($this->usesFields()) {
      $form['col_class_custom']['#description'] .= ' ' . $this->t('You may use field tokens from as per the "Replacement patterns" used in "Rewrite the output of this field" for all fields.');
    }
    $form['row_class_custom'] = [
      '#title' => $this->t('Custom row class'),
      '#description' => $this->t('Additional classes to provide on each row. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['row_class_custom'],
    ];
    if ($this->usesFields()) {
      $form['row_class_custom']['#description'] .= ' ' . $this->t('You may use field tokens from as per the "Replacement patterns" used in "Rewrite the output of this field" for all fields.');
    }
  }

  /**
   * Return the token-replaced row or column classes for the specified result.
   *
   * @param int $result_index
   *   The delta of the result item to get custom classes for.
   * @param string $type
   *   The type of custom grid class to return, either "row" or "col".
   *
   * @return string
   *   A space-delimited string of classes.
   */
  public function getCustomClass($result_index, $type) {
    $class = $this->options[$type . '_class_custom'];
    if ($this->usesFields() && $this->view->field) {
      $class = strip_tags($this->tokenizeValue($class, $result_index));
    }

    $classes = explode(' ', $class);
    foreach ($classes as &$class) {
      $class = Html::cleanCssIdentifier($class);
    }
    return implode(' ', $classes);
  }

}
