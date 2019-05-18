<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Displays a chart that demonstrated panning and zooming.
 */
class Navigate extends ControllerBase {

  /**
   * Content.
   */
  public function content() {
    $data = [];
    $options = [
      'series' => [
        'lines' => ['show' => TRUE],
        'shadowSize' => 0,
      ],
      'xaxis' => [
        'zoomRange' => [0.1, 10],
        'panRange' => [-10, 10],
      ],
      'yaxis' => [
        'zoomRange' => [0.1, 10],
        'panRange' => [-10, 10],
      ],
      'zoom' => ['interactive' => TRUE],
      'pan' => ['interactive' => TRUE],
    ];
    $text = [];
    $text[] = [
      [
        '#markup' => '<p class="message"></p>',
      ],
    ];
    $text[] = $this->t('With the navigate plugin it is easy to add panning and zooming. Drag to pan, double click to zoom (or use the mouse scrollwheel).');
    $text[] = $this->t('The plugin fires events (useful for synchronizing several plots) and adds a couple of public methods so you can easily build a little user interface around it, like the little buttons at the top right in the plot.');
    $output[] = [
      '#type' => 'flot',
      '#data' => $data,
      '#theme' => 'flot_examples',
      '#options' => $options,
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/navigate']],
    ];
    return $output;
  }

}
