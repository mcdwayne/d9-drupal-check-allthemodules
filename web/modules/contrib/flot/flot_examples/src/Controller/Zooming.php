<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart demonstrating zooming in and out.
 */
class Zooming extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {
    // Basic line chart.
    $d = array();
    $x1 = 0;
    $x2 = 3 * pi();
    for ($i = 0; $i <= 100; $i++) {
      $x = $x1 + $i * ($x2 - $x1) / 100;
      $d[] = array($x, sin($x * sin($x)));
    }

    $data[] = array('label' => 'sin(x*sin(x))', 'data' => $d);
    $options_p = [
      'legend' => ['show' => FALSE],
      'series' => [
        'lines' => ['show' => TRUE],
        'points' => ['show' => TRUE],
      ],
      'yaxis' => ['ticks' => 10],
      'selection' => ['mode' => 'xy'],
    ];

    $options_o = [
      'legend' => ['show' => FALSE],
      'series' => [
        'lines' => [
          'show' => TRUE,
          'lineWidth' => 1,
        ],
        'shadowSize' => 0,
      ],
      'xaxis' => ['ticks' => 4],
      'yaxis' => [
        'ticks' => 3,
        'min' => -2,
        'max' => 2,
      ],
      'grid' => ['color' => "#999"],
      'selection' => ['mode' => 'xy'],
    ];

    $text = [];
    $text[] = $this->t('Selection support makes it easy to construct flexible zooming schemes. With a few lines of code, the small overview plot to the right has been connected to the large plot. Try selecting a rectangle on either of them.');
    $output['placeholder'] = [
      '#type' => 'flot',
      '#data' => $data,
      '#options' => $options_p,
      '#theme' => 'flot_examples_zooming',
      '#text' => $text,
    ];
    // This next entry ensures the data is available to the javascript.
    // The JS script provided in the above twig template will handle the
    // rendering.
    // Alternatinvely, a new Element could be made and used which will accept
    // additional data and options parameters to render charts with a defined
    // overview/zoom relationship.
    $output['overview'] = [
      '#type' => 'flot',
      '#data' => $data,
      '#options' => $options_o,
      '#id' => 'overview',
      '#theme' => NULL,
    ];
    return $output;
  }

}
