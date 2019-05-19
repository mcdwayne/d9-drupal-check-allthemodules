<?php

namespace Drupal\views_simplechart\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_simplechart",
 *   title = @Translation("Views Simple Chart"),
 *   help = @Translation("Simple Chart Visualization"),
 *   theme = "views_simplechart_graph",
 *   display_types = {"normal"}
 * )
 */
class ViewsSimplechart extends StylePluginBase {

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
  protected $usesRowClass = FALSE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['chart_title'] = ['default' => 'Simple Chart'];
    $options['chart_axis_mapping'] = ['default' => ''];
    $options['chart_type_stacked'] = ['default' => 'no'];
    $options['chart_type'] = ['default' => 'bar'];
    $options['chart_legend_position'] = ['default' => 'bottom'];
    $options['chart_width'] = ['default' => '400'];
    $options['chart_height'] = ['default' => '300'];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['chart_title'] = [
      '#title' => t('Chart Title'),
      '#type' => 'textfield',
      '#size' => '60',
      '#default_value' => $this->options['chart_title'],
    ];
    $form['chart_axis_mapping'] = [
      '#title' => t('Chart Axis Mapping'),
      '#type' => 'textfield',
      '#description' => t('Each axis need to be placed as comma(,) separtor.'),
      '#size' => '60',
      '#default_value' => $this->options['chart_axis_mapping'],
    ];
    $form['chart_type'] = [
      '#type' => 'radios',
      '#title' => t('Chart type'),
      '#options' => [
        'BarChart' => t('Bar Chart'),
        'PieChart' => t('Pie Chart'),
        'LineChart' => t('Line Chart'),
        'ColumnChart' => t('Column Chart'),
        'Timeline' => t('Timeline'),
        'OrgChart' => t('Organization Chart'),
      ],
      '#default_value' => $this->options['chart_type'],
    ];
    $form['chart_type_stacked'] = [
      '#type' => 'radios',
      '#title' => t('Do you want Stack in Graph?'),
      '#options' => ['yes' => t('Yes'), 'no' => t('No')],
      '#description' => t('This is applicable only for Bar and Column chart.'),
      '#default_value' => $this->options['chart_type_stacked'],
    ];
    $form['chart_legend_position'] = [
      '#type' => 'radios',
      '#title' => t('Chart Legend Position'),
      '#options' => [
        'left' => t('Left'),
        'right' => t('Right'),
        'top' => t('Top'),
        'bottom' => t('Bottom'),
      ],
      '#default_value' => $this->options['chart_legend_position'],
    ];
    $form['chart_width'] = [
      '#title' => t('Chart Width'),
      '#type' => 'textfield',
      '#size' => '60',
      '#default_value' => $this->options['chart_width'],
    ];
    $form['chart_height'] = [
      '#title' => t('Chart Height'),
      '#type' => 'textfield',
      '#size' => '60',
      '#default_value' => $this->options['chart_height'],
    ];
  }

  /**
   * Render fields.
   *
   * @see template_preprocess_views_simplechart_graph()
   */
  public function getRenderFields() {
    return $this->rendered_fields;
  }

}
