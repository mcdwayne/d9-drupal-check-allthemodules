<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart demonstrating pie charts.
 */
class SeriesPie extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {

    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples_series_pie',
      '#data' => [
        ['label' => "Series1", 'data' => 3],
        ['label' => "Series2", 'data' => 30],
      ],
      '#options' => ['series' => ['pie' => ['show' => TRUE]]],
    ];
    return $output;
  }

}
