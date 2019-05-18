<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart demonstrating the different types of series.
 */
class SeriesTypes extends ControllerBase {

  /**
   * Function content.
   */
  public function content() {
    // Line Chart with custom Formatting.
    $sin_data = array();
    for ($i = 0; $i < 14; $i += 0.5) {
      $sin_data[] = array($i, sin($i));
    }
    $series[] = array('data' => $sin_data);
    $series[] = array(
      'data' => array(array(0, 3), array(4, 8), array(8, 5), array(9, 13)),
    );
    $series[] = array(
      'data' => array(
        array(0, 12),
        array(7, 12),
        NULL,
        array(7, 2.5),
        array(12, 2.5),
      ),
    );

    // First add new formatting to existing data.
    $series[0]['lines'] = array('show' => TRUE, 'fill' => TRUE);
    $series[1]['bars'] = array('show' => TRUE);

    $cos_data = array();
    for ($i = 0; $i < 14; $i += 0.5) {
      $cos_data[] = array($i, cos($i));
    }
    $series[2]['data'] = $cos_data;
    $series[2]['points'] = array('show' => TRUE);

    $sqrt_data = array();
    for ($i = 0; $i < 14; $i += 0.1) {
      $sqrt_data[] = array($i, sqrt($i * 10));
    }
    $series[] = array(
      'data' => $sqrt_data,
      'lines' => array('show' => TRUE),
    );

    $sqrt2_data = array();
    for ($i = 0; $i < 14; $i += 0.5) {
      $sqrt2_data[] = array($i, sqrt($i));
    }

    $series[] = array(
      'data' => $sqrt2_data,
      'lines' => array('show' => TRUE),
      'points' => array('show' => TRUE),
    );
    $text = $this->t('Flot supports lines, points, filled areas, bars and any combinations of these, in the same plot and even on the same data series.');
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $series,
      '#theme' => 'flot_examples',
      '#text' => [$text],
    ];
    return $output;
  }

}
