<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a graph demonstrating basic chart options.
 */
class BasicOptions extends ControllerBase {

  /**
   * Function basicOptions.
   */
  public function content() {
    $d1 = array();
    $d2 = array();
    for ($i = 0; $i < pi() * 2; $i += 0.25) {
      $d1[] = array($i, sin($i));
      $d2[] = array($i, cos($i));
    }
    $d3 = array();
    for ($i = 0; $i < pi() * 2; $i += 0.1) {
      $d3[] = array($i, tan($i));
    }

    $series[] = array(
      'label' => 'sin(x)',
      'data' => $d1,
    );
    $series[] = array(
      'label' => 'cos(x)',
      'data' => $d2,
    );
    $series[] = array(
      'label' => 'tan(x)',
      'data' => $d3,
    );

    $options = [
      'series' => [
        'lines' => [
          'show' => TRUE,
        ],
        'points' => [
          'show' => TRUE,
        ],
      ],
      'xaxis' => [
        'ticks' => [
          0,
          [pi() / 2, "π/2"],
          [pi(), "π"],
          [pi() * 3 / 2, "3π/2"],
          [pi() * 2, "2π"],
        ],
      ],
      'yaxis' => [
        'ticks' => 10,
        'min' => -2,
        'max' => 2,
        'tickDecimals' => 3,
      ],
      'grid' => [
        'backgroundColor' => [
          'colors' => ["#fff", "#eee"],
        ],
        'borderWidth' => [
          'top' => 1,
          'right' => 1,
          'bottom' => 2,
          'left' => 2,
        ],
      ],
    ];
    $text = [];
    $text[] = ['value' => $this->t('There are plenty of options you can set to control the precise looks of your plot. You can control the ticks on the axes, the legend, the graph type, etc.')];
    $text[] = ['value' => $this->t("Flot goes to great lengths to provide sensible defaults so that you don't have to customize much for a good-looking result.")];
    $output['flot'] = [
      '#type' => 'flot',
      '#data' => $series,
      '#options' => $options,
      '#theme' => 'flot_examples',
      '#text' => $text,
    ];
    return $output;
  }

}
