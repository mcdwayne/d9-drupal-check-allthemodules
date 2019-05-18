<?php

namespace Drupal\charting\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Chart style plugin.
 *
 * @ViewsStyle(
 *   id = "charting_chart",
 *   title = @Translation("Chart"),
 *   help = @Translation("Render charts."),
 *   theme = "views_style_charting_chart",
 *   display_types = {"normal"}
 * )
 */
class Chart extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['wrapper_class'] = ['default' => 'item-list'];
    $options['chart_type'] = ['default' => 'simple_bars'];
    $options['title_field'] = ['default' => ''];
    $options['value_field'] = ['default' => ''];
    // @see http://there4.io/2012/05/02/google-chart-color-list/
    $options['color_list'] = ['default' => '#3366CC, #DC3912, #FF9900, #109618, #990099, #3B3EAC, #0099C6, #DD4477, #66AA00, #B82E2E, #316395, #994499, #22AA99, #AAAA11, #6633CC, #E67300, #8B0707, #329262, #5574A6, #3B3EAC'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Build select options.
    $labels = $this->displayHandler->getFieldLabels();
    $title_options = [];
    $value_options = [];
    $fields = $this->displayHandler->getOption('fields');
    foreach ($fields as $field_name => $field) {
      $title_options[$field_name] = $labels[$field_name];
      $value_options[$field_name] = $labels[$field_name];
    }

    // Build form.
    $form['wrapper_class'] = [
      '#title' => $this->t('Wrapper class'),
      '#description' => $this->t('The class to provide on the wrapper, outside rows.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['wrapper_class'],
    ];

    // @todo: Do a pluggable model for chart types.
    $form['chart_type'] = [
      '#title' => $this->t('Chart type'),
      '#description' => $this->t('The chart type to use.'),
      '#type' => 'select',
      '#options' => [
        'simple_bars' => $this->t('Simple bars'),
        'chartjs_doughnut' => $this->t('Doughnut (Chart.js)'),
        'chartjs_semi_doughnut' => $this->t('Semi doughnut (Chart.js)'),
        'chartjs_pie' => $this->t('Pie (Chart.js)'),
        'chartjs_semi_pie' => $this->t('Semi pie (Chart.js)'),
        'chartjs_line' => $this->t('Line (Chart.js)'),
        'chartjs_horizontal_bars' => $this->t('Horizontal bars (Chart.js)'),
        'chartjs_vertical_bars' => $this->t('Vertical bars (Chart.js)'),
      ],
      '#default_value' => $this->options['chart_type'],
    ];

    $form['title_field'] = [
      '#title' => $this->t('Title source field'),
      '#type' => 'select',
      '#default_value' => $this->options['title_field'],
      '#description' => $this->t("The source of the title for each item."),
      '#options' => $title_options,
      '#empty_value' => 'none',
    ];

    $form['value_field'] = [
      '#title' => $this->t('Value source field'),
      '#type' => 'select',
      '#default_value' => $this->options['value_field'],
      '#description' => $this->t("The source of the value for each item."),
      '#options' => $value_options,
      '#empty_value' => 'none',
    ];

    $form['color_list'] = [
      '#title' => $this->t('Color list'),
      '#description' => $this->t('A comma separated list of color to use in bars, in compatible CSS color notation.'),
      '#type' => 'textfield',
      '#maxlength' => 1024,
      '#default_value' => $this->options['color_list'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (!empty($this->options['title_field']) && $this->options['title_field'] != 'none') {
      $title_field = $this->options['title_field'];
    }

    if (!empty($this->options['value_field']) && $this->options['value_field'] != 'none') {
      $value_field = $this->options['value_field'];
    }

    if (empty($this->options['color_list'])) {
      $color_list = [];
    }
    else {
      $color_list = $this->options['color_list'];
    }
    // Build usable color list.
    $colors = [];
    // Current color.
    $colorInd = 0;
    $colorList = explode(',', $color_list);
    foreach ($colorList as $color) {
      if (!empty(trim($color))) {
        $colors[] = trim($color);
      }
    }
    $colorLen = count($colors);

    $dom_id = $this->view->dom_id;

    // Set template theme.
    $build = [
      '#theme' => $this->view->buildThemeFunctions('views_style_charting_chart'),
      '#view' => $this->view,
      '#options' => [
        'id' => $dom_id,
        'type' => $this->options['chart_type'],
      ],
    ];
    switch ($this->options['chart_type']) {
      case 'simple_bars':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chart',
          ],
        ];
        break;

      case 'chartjs_doughnut':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chartjs_doughnut',
          ],
        ];
        break;

      case 'chartjs_semi_doughnut':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chartjs_semi_doughnut',
          ],
        ];
        break;

      case 'chartjs_pie':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chartjs_pie',
          ],
        ];
        break;

      case 'chartjs_semi_pie':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chartjs_semi_pie',
          ],
        ];
        break;

      case 'chartjs_line':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chartjs_line',
          ],
        ];
        break;

      case 'chartjs_horizontal_bars':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chartjs_hotizontal_bars',
          ],
        ];
        break;

      case 'chartjs_vertical_bars':
        $build['#attached'] = [
          'library' => [
            'charting/charting_chartjs_vertical_bars',
          ],
        ];
        break;
    }
    $build['#attached']['drupalSettings']['charting']['chart_container_id'][] = $dom_id;

    // Add cache dependency for the view.
    $renderer = $this->getRenderer();
    $renderer->addCacheableDependency($build, $this->view->storage);

    $this->renderFields($this->view->result);

    /*
     * Add points to output.
     */
    foreach ($this->view->result as $row_number => $row) {
      if (!empty($title_field)) {
        if (!empty($this->rendered_fields[$row_number][$title_field])) {
          $title_build = $this->rendered_fields[$row_number][$title_field];
        }
        elseif (!empty($this->view->field[$title_field])) {
          $title_build = $this->view->field[$title_field]->render($row);
        }
      }

      if (!empty($value_field)) {
        $value_build = $this->view->field[$value_field]->render($row);
      }

      $row = [
        'serial' => $row_number,
        'content' => $this->view->rowPlugin->render($row),
        'title' => empty($title_build) ? '' : $title_build,
        'value' => empty($value_build) ? 0 : $value_build,
        'color' => $colorLen > 0 ? $colors[$colorInd++] : '',
      ];
      if ($colorInd >= $colorLen) {
        $colorInd = 0;
      }

      $build['#rows'][] = $row;
    }

    return $build;
  }

}
