<?php

namespace Drupal\flot_views_time\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render dates and values as a bar, scatter, or line chart.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "flot_views_time",
 *   title = @Translation("Time-series Chart"),
 *   help = @Translation("Render a set of data into a time series."),
 *   theme = "views_view_flot_views_time",
 *   display_types = { "normal" }
 * )
 */
class FlotTime extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['columns'] = ['default' => []];
    $options['info'] = ['default' => []];
    return $options;
  }
  public function sanitizeColumns($columns, $fields = NULL) {
    $sanitized = array();
    if ($fields === NULL) {
      $fields = $this->displayHandler->getOption('fields');
    }
    // Preconfigure the sanitized array so that the order is retained.
    foreach ($fields as $field => $info) {
      // Set to itself so that if it isn't touched, it gets column
      // status automatically.
      $sanitized[$field] = $field;
    }

    foreach ($columns as $field => $column) {
      // first, make sure the field still exists.
      if (!isset($sanitized[$field])) {
        continue;
      }

      // If the field is the column, mark it so, or the column
      // it's set to is a column, that's ok
      if ($field == $column || $columns[$column] == $column && !empty($sanitized[$column])) {
        $sanitized[$field] = $column;
      }
      // Since we set the field to itself initially, ignoring
      // the condition is ok; the field will get its column
      // status back.
    }

    return $sanitized;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = array(
        '#markup' => '<div class="messages messages--error">' . $this->t('You need at least one field before you can configure your table settings') . '</div>',
      );
      return;
    }

    // Create an array of allowed columns from the data we know:
    $field_names = $this->displayHandler->getFieldLabels();
    $number_fields = count($field_names);
    $form['#theme'] = 'views_ui_style_plugin_flot_views_table';
    $columns['x'] = $this->sanitizeColumns($this->options['columns']);
    $columns['y'] = $columns['x'];
    $i = 0;
    for ($i = 0; $i < $number_fields - 1; $i++) {
      $field = array_slice($field_names, $i, 1);
      $field_name = key($field);
      $column = $field_names[$field_name];

      $form['columns']['x'][$i] = array(
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => $field_names,
        '#default_value' => $this->options['columns']['x'][$i],
      );
      $form['columns']['y'][$i] = array(
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => $field_names,
        '#default_value' => $this->options['columns']['y'][$i],
      );
      $form['info'][$i]['points'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display Points'),
        '#title_display' => 'invisible',
        '#default_value' => $this->options['info'][$i]['points'],
      ];
      $form['info'][$i]['lines'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display Lines'),
        '#title_display' => 'invisible',
        '#default_value' => $this->options['info'][$i]['lines'],
      ];
      $form['info'][$i]['second_axis'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Second Axis'),
        '#title_display' => 'invisible',
        '#default_value' => isset($this->options['info'][$i]['second_axis']) ? $this->options['info'][$i]['second_axis'] : FALSE,
      ];

    }

    
  }
   
}
