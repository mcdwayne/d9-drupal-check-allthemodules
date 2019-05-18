<?php

namespace Drupal\simple_timeline\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "simple_timeline",
 *   title = @Translation("Simple Timeline"),
 *   help = @Translation("Displays rows as a timeline."),
 *   theme = "views_view_simple_timeline",
 *   display_types = {"normal"}
 * )
 */
class SimpleTimeline extends StylePluginBase {

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
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['position_items'] = ['default' => 'alternate'];
    $options['position_marker'] = ['default' => 'marker-center'];
    $options['class'] = ['default' => 'item-list'];
    $options['wrapper_class'] = ['default' => 'wrapper-list'];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['position_items'] = [
      '#type' => 'radios',
      '#title' => $this->t('Items position'),
      '#options' => [
        'alternate' => $this->t('Alternate position (contents are alternated (left and right, etc.) around the timeline)'),
        'left' => $this->t('Left position (contents are positionned to the left of the timeline)'),
        'right' => $this->t('Right position (contents are positionned to the right of the timeline)'),
      ],
      '#default_value' => $this->options['position_items'],
      '#description' => $this->t('Select the position of contents around the timeline.'),
    ];
    $form['position_marker'] = [
      '#type' => 'radios',
      '#title' => $this->t('Marker position'),
      '#options' => [
        'marker-top' => $this->t('Top'),
        'marker-center' => $this->t('Center'),
        'marker-bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->options['position_marker'],
      '#description' => $this->t('Select the marker position relative to the element.'),
    ];
    $form['wrapper_class'] = [
      '#title' => $this->t('Wrapper class'),
      '#description' => $this->t('The class to provide on the wrapper, outside the list.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['wrapper_class'],
    ];
    $form['class'] = [
      '#title' => $this->t('List class'),
      '#description' => $this->t('The class to provide on the list element itself.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['class'],
    ];
  }

}
