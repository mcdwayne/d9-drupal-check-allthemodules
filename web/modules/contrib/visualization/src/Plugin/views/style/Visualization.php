<?php
/**
 * @file
 * Definition of Drupal\visualization\Plugin\views\style\Visualization.
 */

namespace Drupal\visualization\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

/** Style plugin uses views ui to configure views data for rendering charts.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "visualization",
 *   title = @Translation("Visualization"),
 *   module = "visualization",
 *   theme = "visualization",
 *   help = @Translation("Display the resulting data set as a chart."),
 *   display_types = {"normal"}
 * )
 */

class Visualization extends StylePluginBase {
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
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = array('default' => 'line');
    $options['fields'] = array('default' => array());

    $options['yAxis']['contains']['title'] = array('default' => '');
    $options['xAxis']['contains']['labelField'] = array('default' => FALSE);
    $options['xAxis']['contains']['invert'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Returns the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $handlers = $this->displayHandler->getHandlers('field');
    $fields = $this->displayHandler->getFieldLabels();

    $form['type'] = array(
      '#type' => 'select',
      '#title' => t('Chart type'),
      '#options' => array(
        'line' => 'Line chart',
        'pie' => 'Pie chart',
        'bar' => 'Bar chart',
        'column' => 'Column chart',
      ),
      '#default_value' => $this->options['type'],
      '#empty_value' => FALSE,
    );

    $form['fields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Field settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    foreach ($fields as $field => $column) {
      $handler = $handlers[$field];

      $form['fields'][$field] = array(
        '#type' => 'details',
        '#title' => SafeMarkup::checkPlain($field),
        '#collapsible' => TRUE,
        '#collapsed' => empty($this->options['fields'][$field]),
      );

      $form['fields'][$field]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable this field in the chart'),
        '#default_value' => empty($this->options['fields'][$field]['enabled']) ? FALSE : $this->options['fields'][$field]['enabled'],
        '#dependency' => array('style_options', 'fields', $field, 'type'),
      );

      if ($handler->clickSortable()) {
        $form['fields'][$field]['sort'] = array(
          '#type' => 'select',
          '#title' => t('Sort'),
          '#options' => array(
            'DESC' => t('Descending'),
            'ASC' => t('Ascending'),
          ),
          '#default_value' => empty($this->options['fields'][$field]['sort']) ? FALSE : $this->options['fields'][$field]['sort'],
          '#empty_value' => FALSE,
        );
      }
    }

    $form['xAxis'] = array(
      '#type' => 'fieldset',
      '#title' => t('X-axis settings'),
      '#collapsible' => TRUE,
      '#collapsed' => !empty($this->options['xAxis']['labelField']) || !empty($this->options['xAxis']['invert']),
    );

    $form['xAxis']['labelField'] = array(
      '#type' => 'select',
      '#title' => t('X-axis labels'),
      '#options' => $fields,
      '#default_value' => $this->options['xAxis']['labelField'],
      '#empty_value' => FALSE,
    );

    $form['xAxis']['invert'] = array(
      '#type' => 'checkbox',
      '#title' => t('Should the x-axis get inverted?'),
      '#default_value' => $this->options['xAxis']['invert'],
    );

    $form['yAxis'] = array(
      '#type' => 'fieldset',
      '#title' => t('Y-axis settings'),
      '#collapsible' => TRUE,
      '#collapsed' => empty($this->options['yAxis']['title']),
    );

    $form['yAxis']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Y-axis title'),
      '#default_value' => $this->options['yAxis']['title'],
    );
  }

  /**
   * Adds sorting to the individual fields.
   */
  public function build_sort() {
    foreach ($this->options['fields'] as $field => $option) {
      $handler = $this->displayHandler->handlers['field'][$field];

      if ($option['sort'] && $handler->click_sortable()) {
        $handler->click_sort($option['sort']);
      }
    }

    return FALSE;
  }

  /**
   * @see template_preprocess_visualization()
   * @return array|null
   */
  public function get_render_fields() {
    return $this->rendered_fields;
  }
}
