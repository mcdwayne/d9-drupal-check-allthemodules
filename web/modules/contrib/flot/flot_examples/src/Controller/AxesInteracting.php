<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Displays a chart to demonstrate interacting with the axes.
 */
class AxesInteracting extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {
    $data = array();
    $series = array();
    for ($i = 0; $i <= 100; $i++) {
      $x = $i / 10;
      $data[] = [$x, sqrt($x)];
    }
    $series[] = [
      'data' => $data,
      'xaxis' => 1,
      'yaxis' => 1,
    ];

    $data = array();
    for ($i = 0; $i <= 100; $i++) {
      $x = $i / 10;
      $data[] = [$x, sin($x)];
    }
    $series[] = [
      'data' => $data,
      'xaxis' => 1,
      'yaxis' => 2,
    ];

    $data = array();
    for ($i = 0; $i <= 100; $i++) {
      $x = $i / 10;
      $data[] = [$x, cos($x)];
    }
    $series[] = [
      'data' => $data,
      'xaxis' => 1,
      'yaxis' => 3,
    ];

    $data = array();
    for ($i = 0; $i <= 100; $i++) {
      $x = 2 + $i * 8 / 100;
      $data[] = [$x, tan($x)];
    }
    $series[] = [
      'data' => $data,
      'xaxis' => 2,
      'yaxis' => 4,
    ];

    $options = [
      'xaxes' => [
        ['position' => 'bottom'],
        ['position' => 'top'],
      ],
      'yaxes' => [
        ['position' => 'left'],
        ['position' => 'left'],
        ['position' => 'right'],
        ['position' => 'left'],
      ],
    ];
    $text = [];
    $text[] = $this->t('With multiple axes, you sometimes need to interact with them. A simple way to do this is to draw the plot, deduce the axis placements and insert a couple of divs on top to catch events.');
    $text[] = $this->t('Try clicking an axis.');
    $text[] = [
      '#markup' => $this->t('<p id="click"></p>'),
    ];

    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $series,
      '#options' => $options,
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/axes_interacting']],
    ];
    return $output;
  }

}
