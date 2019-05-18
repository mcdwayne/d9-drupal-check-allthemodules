<?php

namespace Drupal\sooperthemes_gridstack\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "sooperthemes_gridstack_gridstack",
 *   title = @Translation("Gridstack"),
 *   help = @Translation("Displays rows as Gridstack."),
 *   theme = "sooperthemes_gridstack_gridstack_style",
 *   display_types = {"normal"}
 * )
 */
class Gridstack extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['gridstack_layout'] = ['default' => 'custom'];
    $options['gridstack_items'] = ['default' => '3'];
    $options['gridstack_gap'] = ['default' => '0'];
    $options['gridstack_overlay'] = ['default' => 'dark'];
    $options['gridstack_zoom'] = ['default' => 1];
    $options['gridstack_layout_data']['default'] = '[{"x":0,"y":0,"width":6,"height":6},{"x":6,"y":0,"width":3,"height":6},{"x":9,"y":0,"width":3,"height":6}]';
    $options['gridstack_items_mobile'] = ['default' => 1];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['#prefix'] = '<div id="sooperthemes-gridstack-grid-stack-admin">';
    $form['#sufix'] = '</div>';
    $form['gridstack_layout'] = [
      '#type' => 'select',
      '#title' => t('Layout Presets'),
      '#options' => [
        'custom' => t('Custom'),
        'example_1' => t('5 Items'),
        'example_2' => t('4 Items'),
        'example_3' => t('7 Items'),
      ],
      '#default_value' => $this->options['gridstack_layout'],
    ];
    $form['gridstack_items'] = [
      '#type' => 'number',
      '#title' => t('Number of items'),
      '#default_value' => $this->options['gridstack_items'],
      '#min' => 1,
      '#max' => 100,
    ];
    $form['gridstack_overlay'] = [
      '#type' => 'select',
      '#title' => t('Image overlay effect'),
      '#options' => [
        '' => t('None'),
        'dark' => t('Dark'),
        'light' => t('Light'),
        'rainbow' => t('Rainbow'),
      ],
      '#default_value' => $this->options['gridstack_overlay'],
    ];
    $form['more'] = [
      '#type' => 'details',
      '#title' => t('More settings'),
    ];
    $form['more']['gridstack_zoom'] = [
      '#type' => 'checkbox',
      '#title' => t('Image zoom effect on hover'),
      '#default_value' => $this->options['gridstack_zoom'],
    ];
    $form['more']['gridstack_gap'] = [
      '#type' => 'number',
      '#title' => t('Gap size'),
      '#default_value' => $this->options['gridstack_gap'],
      '#min' => 0,
    ];
    $form['more']['gridstack_items_mobile'] = [
      '#type' => 'number',
      '#title' => t('Number of items in mobile view'),
      '#default_value' => $this->options['gridstack_items_mobile'],
      '#min' => 1,
      '#max' => 100,
    ];
    $form['gridstack_layout_template'] = [
      '#prefix' => '<h3>' . t('Modify layout (drag and drop)') . '</h3>',
      '#markup' => '<div class="grid-stack"></div>',
    ];
    $form['more']['gridstack_layout_data'] = [
      '#type' => 'textarea',
      '#title' => t('Custom layout data'),
      '#default_value' => $this->options['gridstack_layout_data'],
      '#description' => t('Define `media queries` for columns/rows layout in JSON format. As example you can see predefined layouts.'),
    ];

    $form['#attached']['drupalSettings']['sooperthemesGridStack']['layoutDataAdmin'] = $form['more']['gridstack_layout_data']['#default_value'];
    $form['#attached']['library'][] = 'sooperthemes_gridstack/admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    // Plugin options form doesn't respect #tree property.
    $style_options = $form_state->getValue('style_options');
    foreach ($style_options['more'] as $option_name => $option) {
      $style_options[$option_name] = $option;
    }
    unset($style_options['more']);
    $form_state->setValue('style_options', $style_options);
  }

}
