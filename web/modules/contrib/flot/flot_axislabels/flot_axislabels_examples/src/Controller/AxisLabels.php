<?php

namespace Drupal\flot_axislabels_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart with minimal options.
 */
class AxisLabels extends ControllerBase {

  /**
   * Function content.
   */
  public function content() {
    // Basic line chart.
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
    $options = [
      'axisLabels' => ['show' => true],
      'xaxes' => [
        [
            axisLabel => 'foo',
        ]
      ],
      'yaxes' => [
        [
            position => 'left',
            axisLabel => 'bar',
        ],
      ],
    ];
    $text = [];
    $text[] = $this->t("You don't have to do much to get an attractive plot.  Create a placeholder, make sure it has dimensions (so Flot knows at what size to draw the plot), then call the plot function with your data.");
    $text[] = $this->t('The axes are automatically scaled.');
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $series,
      '#theme' => 'flot_examples',
      '#text' => $text,
      '#options' => $options,
      '#attached' => ['library' => ['flot_axislabels/flot_axislabels']],
    ];
    return $output;
  }

}
