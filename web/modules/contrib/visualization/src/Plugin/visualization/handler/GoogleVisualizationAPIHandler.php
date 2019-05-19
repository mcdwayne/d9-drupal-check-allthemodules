<?php
/**
 * @file
 * Drupal\visualization\Plugin\visualization\handler\GoogleVisualizationAPIHandler.
 */

namespace Drupal\visualization\Plugin\visualization\handler;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\visualization\VisualizationHandlerInterface;
/**
 * Library plugin for Visualization implementing support for
 * Google Visualization API.
 *
 * @Plugin(
 *   id = "gva",
 *   name = "gva",
 *   label = @Translation("Google Visualization API")
 * )
 */
class GoogleVisualizationAPIHandler implements VisualizationHandlerInterface {

  public $name = 'gva';

  protected $addedJavascript = FALSE;

  /**
   * Renders a chart using the Google Visualization API.
   */
  public function render($chart_id, $data, $options) {
    // Chart options.
    $chart_options = array(
      'title' => $options['title'],
      'width' => !empty($options['width']) ? $options['width'] : '100%',
      'height' => !empty($options['height']) ? $options['height'] : '100%',
    );

    switch ($options['type']) {
      case 'map':
        $chart_options['dataMode'] = !empty($options['dataMode']) ? $options['dataMode'] : 'regions';
        break;
    }

    // Prepare the table array with the data.
    $table_data = array();

    // Add header row first.
    $header = array();

    if (!empty($options['xAxis']['labelField'])) {
      $header[] = $options['fields'][$options['xAxis']['labelField']]['label'];
    }

    foreach ($options['fields'] as $name => $column) {
      if (!empty($column['enabled'])) {
        $header[] = $column['label'];
      }
    }

    $table_data[] = $header;

    // Then add data, row per row.
    foreach ($data as $row) {
      $table_row = array();

      if (!empty($options['xAxis']['labelField'])) {
        $table_row[] = SafeMarkup::checkPlain(strip_tags((string) $row[$options['xAxis']['labelField']]));
      }

      foreach ($options['fields'] as $name => $column) {
        if (!empty($column['enabled'])) {
          $value = is_null($row[$name]) ? NULL : (float) $row[$name]->__toString();

          $table_row[] = $value;
        }
      }

      $table_data[] = $table_row;
    }

    $information = array(
      'library' => 'google_visualization',
      'type' => $options['type'],
      'options' => $chart_options,
      'dataArray' => $table_data,
      'chart_id' => $chart_id,
    );

    // Add Drupal.settings for this chart.
    $chart['#attached'] = [
      'drupalSettings'=> [
        'visualization' => [$chart_id => $information],
      ]
    ];

    return $chart;
  }

  /**
   * Loads the global Javascript required by the Google Visualization API.
   */
  public function postRender() {
    if (!$this->addedJavascript) {
      $js_libs['#attached']['library'][] = 'visualization/gva';
      drupal_render($js_libs);

      $this->addedJavascript = TRUE;
    }
  }

  /**
   * Returns whether or not the plugin is available.
   */
  public function available() {
    return TRUE;
  }

  /**
   * Returns an array of supported chart types.
   */
  public function supportedTypes() {
    return array('line', 'column', 'pie', 'map');
  }

}
