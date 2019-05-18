<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Displays a chart to demonstrate the annotating features of FLOT.
 */
class Annotating extends ControllerBase {

  /**
   * Function realtime.
   */
  public function content() {
    $d1 = array();
    for ($i = 0; $i < 20; $i++) {
      $d1[] = array($i, sin($i));
    }

    $data[] = array(
      'data' => $d1,
      'label' => $this->t('Pressure'),
      'color' => '#333',
    );

    $markings = [
      [
        'color' => '#f6f6f6',
        'yaxis' => ['from' => 1],
      ],
      [
        'color' => '#f6f6f6',
        'yaxis' => ['to' => -1],
      ],
      [
        'color' => '#000',
        'lineWidth' => 1,
        'xaxis' => [
          'from' => 2,
          'to' => 2,
        ],
      ],
      [
        'color' => '#000',
        'lineWidth' => 1,
        'xaxis' => [
          'from' => 8,
          'to' => 8,
        ],
      ],
    ];

    $options = [
      'bars' => [
        'show' => TRUE,
        'barWidth' => .5,
        'fill' => 0.9,
      ],
      'xaxis' => [
        'ticks' => [],
        'autoscaleMargin' => .02,
      ],
      'yaxis' => [
        'min' => -2,
        'max' => 2,
      ],
      'grid' => ['markings' => $markings],
    ];
    $text = $this->t('Flot has support for simple background decorations such as lines and rectangles. They can be useful for marking up certain areas. You can easily add any HTML you need with standard DOM manipulation, e.g. for labels. For drawing custom shapes there is also direct access to the canvas.');
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $data,
      '#options' => $options,
      '#theme' => 'flot_examples',
      '#text' => [$text],
      '#attached' => ['library' => ['flot_examples/annotating']],
    ];
    return $output;
  }

}
