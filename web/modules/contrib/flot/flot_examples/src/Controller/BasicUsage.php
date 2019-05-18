<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
/**
 * Display a chart with minimal options.
 */
class BasicUsage extends ControllerBase {

  /**
   * Function content.
   */
  public function content() {

    // Basic line chart.
    $sin_data = [];
    for ($i = 0; $i < 14; $i += 0.5) {
      $sin_data[] = [$i, sin($i)];
    }
    $series[] = ['data' => $sin_data];
    $series[] = [
      'data' => [[0, 3], [4, 8], [8, 5], [9, 13]],
    ];
    $series[] = [
      'data' => [
        [0, 12],
        [7, 12],
        NULL,
        [7, 2.5],
        [12, 2.5],
      ],
    ];
    $text = [];
    $text[] = $this->t('You don\'t have to do much to get an attractive plot.  Create a placeholder, make sure it has dimensions (so Flot knows at what size to draw the plot), then call the plot function with your data.');
    $text[] = $this->t('The axes are automatically scaled.');
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $series,
      '#theme' => 'flot_examples',
      '#text' => $text,
    ];
    return $output;
  }

}
