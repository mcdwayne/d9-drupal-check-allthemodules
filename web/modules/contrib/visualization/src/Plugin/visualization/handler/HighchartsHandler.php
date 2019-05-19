<?php
/**
 * @file
 * Drupal\visualization\Plugin\visualization\handler\HighchartsHandler.
 */

namespace Drupal\visualization\Plugin\visualization\handler;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\visualization\VisualizationHandlerInterface;
/**
 * Library plugin for Visualization implementing support for Highcharts.
 *
 * @Plugin(
 *   id = "highcharts",
 *   name = "highcharts",
 *   label = @Translation("Highcharts")
 * )
 */
class HighchartsHandler implements VisualizationHandlerInterface {

  public $name = 'highcharts';

  protected $addedJavascript = FALSE;
  protected $available = FALSE;

  /**
   * Constructor for the Highcharts plugin.
   */
  public function __construct() {
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      module_load_include('module', 'libraries', 'libraries');
      $path = libraries_get_path('highcharts');

      $this->available = $path ? TRUE : FALSE;
    }
  }

  /**
   * Renders a chart using Highcharts.
   */
  public function render($chart_id, $data, $options) {
    // Chart options.
    $highchart = new \stdClass();

    // Chart.
    $highchart->chart = (object) array(
      'plotBackgroundColor' => NULL,
      'plotBorderWidth' => NULL,
      'plotShadow' => FALSE,
    );

    // Set title.
    $highchart->title = new \stdClass();
    $highchart->title->text = $options['title'];

    $x_axis = array();
    if (!empty($options['xAxis']['labelField'])) {
      foreach ($data as $row) {
        $x_axis[] = SafeMarkup::checkPlain(strip_tags((string) $row[$options['xAxis']['labelField']]));
      }
    }

    if (!empty($x_axis)) {
      $highchart->xAxis = (object) array(
        'categories' => $x_axis,
      );
    }

    if (!empty($options['yAxis']['title'])) {
      $highchart->yAxis = (object) array(
        'title' => (object) array(
          'text' => $options['yAxis']['title'],
        ),
      );
    }

    // Series.
    $highchart->series = array();
    foreach ($options['fields'] as $name => $column) {
      if (!empty($column['enabled'])) {
        $serie = new \stdClass();
        $serie->name = $column['label'];
        $serie->type = $options['type'];

        $serie->data = array();
        foreach ($data as $row) {
          $value = (int) $row[$name];

          if (!empty($column['enabled'])) {
            $serie->data[] = (object) array('name' => SafeMarkup::checkPlain(strip_tags($row[$options['xAxis']['labelField']])), 'y' => $value);
          }
          else {
            $serie->data[] = $value;
          }
        }

        $highchart->series[] = $serie;
      }
    }

    $highchart->plotOptions = new \stdClass();
    $highchart->plotOptions->pie = (object) array(
      'allowPointSelect' => TRUE,
      'cursor' => 'pointer',
      'dataLabels' => (object) array(
        'enabled' => TRUE,
      ),
      'showInLegend' => TRUE,
    );
    $highchart->plotOptions->bar = (object) array(
      'dataLabels' => (object) array(
        'enabled' => TRUE,
      ),
    );

    $highchart->credits = new \stdClass();
    $highchart->credits->enabled = FALSE;

    // Rendering.
    $highchart->chart->renderTo = $chart_id;

    $information = array(
      'library' => 'highcharts',
      'options' => $highchart,
    );

    // Add Drupal.settings for this chart.
    $chart['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array ('visualization' => array($chart_id => $information)),
    );

    return $chart;
  }

  /**
   * Includes global Javascript required by Highcharts.
   */
  public function postRender() {
    if (!$this->addedJavascript) {
      module_load_include('module', 'libraries', 'libraries');
      $path = libraries_get_path('highcharts');

      $js_libs['#attached']['js'] = array(
        array('type' => 'file', 'data' => $path . '/js/highcharts.js'),
        array('type' => 'file', 'scope' => 'footer', 'data' => drupal_get_path('module', 'visualization') . '/js/visualization.js'),
        array('type' => 'file', 'scope' => 'footer', 'data' => drupal_get_path('module', 'visualization') . '/js/highcharts.js'),
      );

      drupal_render($js_libs);

      $this->addedJavascript = TRUE;
    }
  }

  /**
   * Whether or not the plugin is avialable.
   */
  public function available() {
    return $this->available;
  }

  /**
   * Returns an array with supported chart types.
   */
  public function supportedTypes() {
    return array('line', 'column', 'pie');
  }

}
