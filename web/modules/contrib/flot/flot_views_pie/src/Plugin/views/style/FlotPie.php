<?php

namespace Drupal\flot_views_pie\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a list of numbers as a pie chart.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "flot_views_pie",
 *   title = @Translation("Pie Chart"),
 *   help = @Translation("Render a set of data into a pie chart."),
 *   theme = "views_view_flot_views_pie",
 *   display_types = { "normal" }
 * )
 */
class FlotPie extends StylePluginBase {

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['labels'] = ['default' => ''];
    $options['values'] = ['default' => ''];
    $options['pie_or_bar'] = ['default' => 'pie'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Create an array of allowed columns from the data we know.
    $field_names = $this->displayHandler->getFieldLabels();
    $form['labels'] = [
      '#title' => $this->t('The field that contains the chart labels'),
      '#type' => 'select',
      '#options' => $field_names,
      '#default_value' => $this->options['labels'],
    ];
    $form['values'] = [
      '#title' => $this->t('The field that contains the chart data'),
      '#type' => 'select',
      '#options' => $field_names,
      '#default_value' => $this->options['values'],
    ];
    $form['pie_or_bar'] = [
      '#type' => 'radios',
      '#title' => $this->t('Chart Type'),
      '#options' => array('pie' => $this->t('Pie Chart'), 'bar' => $this->t('Bar Chart')),
      '#default_value' => $this->options['pie_or_bar'],
      '#description' => $this->t('Should the categorical data be rendered in a pue chart or bar chart?'),
    ];
  }

}
