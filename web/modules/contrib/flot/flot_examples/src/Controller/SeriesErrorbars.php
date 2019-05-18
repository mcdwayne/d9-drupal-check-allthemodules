<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a graph demonstating the errorbar plugin.
 */
class SeriesErrorbars extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {
    $data1 = [
      [1, 1, .5, .1, .3],
      [2, 2, .3, .5, .2],
      [3, 3, .9, .5, .2],
      [1.5, -.05, .5, .1, .3],
      [3.15, 1., .5, .1, .3],
      [2.5, -1., .5, .1, .3],
    ];

    $data1_points = [
      'show' => TRUE,
      'radius' => 5,
      'fillColor' => "blue",
      'errorbars' => "xy",
      'xerr' => [
        'show' => TRUE,
        'asymmetric' => TRUE,
        'upperCap' => "-",
        'lowerCap' => "-",
      ],
      'yerr' => ['show' => TRUE, 'color' => "red", 'upperCap' => "-"],
    ];

    $data2 = [
      [.7, 3, .2, .4],
      [1.5, 2.2, .3, .4],
      [2.3, 1, .5, .2],
    ];

    $data2_points = [
      'show' => TRUE,
      'radius' => 5,
      'errorbars' => "y",
      'yerr' => [
        'show' => TRUE,
        'asymmetric' => TRUE,
        'upperCap' => 'drawArrow',
        'lowerCap' => 'drawSemiCircle',
      ],
    ];

    $data3 = [
      [1, 2, .4],
      [2, 0.5, .3],
      [2.7, 2, .5],
    ];

    $data3_points = [
      // Do not show points.
      'radius' => 0,
      'errorbars' => "y",
      'yerr' => [
        'show' => TRUE,
        'upperCap' => "-",
        'lowerCap' => "-",
        'radius' => 5,
      ],
    ];

    $data4 = [
      [1.3, 1],
      [1.75, 2.5],
      [2.5, 0.5],
    ];
    $data4_errors = [0.1, 0.4, 0.2];
    for ($i = 0; $i < 3; $i++) {
      $temp = $data4_errors[$i];
      $data4_errors[$i] = $data4[$i];
      $data4_errors[$i][2] = $temp;
    }

    $data = [
      [
        'color' => "blue",
        'points' => $data1_points,
        'data' => $data1,
        'label' => "data1",
      ],
      [
        'color' => "red",
        'points' => $data2_points,
        'data' => $data2,
        'label' => "data2",
      ],
      [
        'color' => "green",
        'lines' => ['show' => TRUE],
        'points' => $data3_points,
        'data' => $data3,
        'label' => "data3",
      ],
      // Bars with errors.
      [
        'color' => "orange",
        'bars' => ['show' => TRUE, 'align' => "center", 'barWidth' => 0.25],
        'data' => $data4,
        'label' => "data4",
      ],
      ['color' => "orange", 'points' => $data3_points, 'data' => $data4_errors],
    ];

    $options = [
      'legend' => [
        'position' => "sw",
        'show' => TRUE,
      ],
      'series' => [
        'lines' => [
          'show' => FALSE,
        ],
      ],
      'xaxis' => [
        'min' => 0.6,
        'max' => 3.1,
      ],
      'yaxis' => [
        'min' => 0,
        'max' => 3.5,
      ],
      'zoom' => ['interactive' => TRUE],
      'pan' => ['interactive' => TRUE],
    ];
    $text = [];
    $text[] = $this->t('With the errorbars plugin you can plot error bars to show standard deviation and other useful statistical properties.');
    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#attached' => ['library' => ['flot_examples/series_errorbars']],
      '#text' => $text,
    ];
    return $output;
  }

}
