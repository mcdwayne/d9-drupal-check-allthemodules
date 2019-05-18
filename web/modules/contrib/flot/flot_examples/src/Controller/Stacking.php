<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart demonstrating stacking bar and line charts.
 */
class Stacking extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {
    /**
     * Generate an array of random values.
     */
    function randArray() {
      $arr = array();
      for ($i = 0; $i <= 10; $i++) {
        $arr[] = [$i, round(rand() / getrandmax() * 30, 0)];
      }
      return $arr;
    }

    $d1 = ['data' => randArray()];
    $d2 = ['data' => randArray()];
    $d3 = ['data' => randArray()];
    $data = [$d1, $d2, $d3];

    $options = [
      'series' => [
        'stack' => TRUE,
        'lines' => [
          'show' => FALSE,
          'fill' => TRUE,
          'steps' => FALSE,
        ],
        'bars' => [
          'show' => TRUE,
          'barWidth' => 0.6,
        ],
      ],
    ];
    $text = [];
    $text[] = $this->t('With the stack plugin, you can have Flot stack the series. This is useful if you wish to display both a total and the constituents it is made of. The only requirement is that you provide the input sorted on x.');
    $text[] = [
      [
        '#markup' => '<p class="stackControls">',
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('With stacking'),
        '#attributes' => ['id' => ['stacking']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Without stacking'),
        '#attributes' => ['id' => ['nostacking']],
      ],
      [
        '#markup' => '</p>',
      ],
    ];

    $text[] = [
      [
        '#markup' => '<p class="graphControls">',
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Bars'),
        '#attributes' => ['id' => ['Bars']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Lines'),
        '#attributes' => ['id' => ['Lines']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Lines with steps'),
        '#attributes' => ['id' => ['steps']],
      ],
      [
        '#markup' => '</p>',
      ],
    ];
    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/stack']],
    ];
    return $output;
  }

}
