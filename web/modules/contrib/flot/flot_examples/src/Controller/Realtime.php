<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a graph demonstrating real-time updating.
 */
class RealTime extends ControllerBase {

  /**
   * Function content.
   */
  public function content() {
    $options = [
      'series' => ['shadowSize' => 0],
      'yaxis' => [
        'min' => 0,
        'max' => 100,
        'show' => TRUE,
      ],
      'xaxis' => ['show' => FALSE],
    ];
    $text = [];
    $text[] = $this->t('You can update a chart periodically to get a real-time effect by using a timer to insert the new data in the plot and redraw it.');
    $text[] = [
      [
        '#type' => 'textfield',
        '#title' => $this->t('Time between updates:'),
        '#attributes' => [
          'class' => ['inline_parent_div'],
          'id' => ['updateInterval'],
        ],
        '#value' => 30,
        '#size' => 10,
      ],
      $this->t('milliseconds'),
    ];
    $output['flot'] = [
      '#type' => 'flot',
      '#options' => $options,
      '#theme' => 'flot_examples',
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/realtime']],
    ];
    return $output;
  }

}
