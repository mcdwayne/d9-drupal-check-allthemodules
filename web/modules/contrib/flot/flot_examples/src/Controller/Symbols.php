<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Generate a chart using different point symbols.
 */
class Symbols extends ControllerBase {

  /**
   * Symbols.
   */
  public function content() {
    $data = [];
    $series = $this->generate(2, 1.8);
    $data[] = [
      'data' => $series,
      'points' => ['symbol' => 'circle'],
    ];
    $series = $this->generate(3, 1.5);
    $data[] = [
      'data' => $series,
      'points' => ['symbol' => 'square'],
    ];
    $data[] = [
      'data' => $this->generate(4, .9),
      'points' => ['symbol' => 'diamond'],
    ];
    $data[] = [
      'data' => $this->generate(6, 1.4),
      'points' => ['symbol' => 'triangle'],
    ];
    $data[] = [
      'data' => $this->generate(7, 1.1),
      'points' => ['symbol' => 'cross'],
    ];

    $options = [
      'series' => [
        'points' => [
          'show' => TRUE,
          'radius' => 3,
        ],
      ],
      'grid' => ['hoverable' => TRUE],
    ];
    $text = [];
    $text[] = $this->t('Points can be marked in several ways, with circles being the built-in default. For other point types, you can define a callback function to draw the symbol. Some common symbols are available in the symbol plugin.');
    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#options' => $options,
      '#data' => $data,
      '#text' => $text,
    ];
    return $output;
  }

  /**
   * Generate sin waves.
   */
  private function generate($offset, $amplitude) {
    $res = [];
    $start = 0;
    $end = 10;
    for ($i = 0; $i <= 50; $i++) {
      $x = start + $i / 50 * ($end - $start);
      $res[] = [$x, $amplitude * sin($x + $offset)];
    }
    return $res;
  }

}
