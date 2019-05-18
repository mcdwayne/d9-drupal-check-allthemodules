<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Displays a chart that demonstrates interacting with the data.
 */
class Interacting extends ControllerBase {

  /**
   * Interacting.
   */
  public function content() {
    $sin = [];
    $cos = [];
    for ($i = 0; $i < 14; $i += 0.5) {
      $sin[] = [$i, sin($i)];
      $cos[] = [$i, cos($i)];
    }
    $data[] = [
      'data' => $sin,
      'label' => 'sin(x)',
    ];
    $data[] = [
      'data' => $cos,
      'label' => 'cos(x)',
    ];
    $options = [
      'series' => [
        'lines' => ['show' => TRUE],
        'points' => ['show' => TRUE],
      ],
      'grid' => [
        'hoverable' => TRUE,
        'clickable' => TRUE,
      ],
      'yaxis' => [
        'min' => -1.2,
        'max' => 1.2,
      ],
    ];
    $text = [];
    $text[] = $this->t('One of the goals of Flot is to support user interactions. Try pointing and clicking on the points.');
    $text[] = [
      [
        '#type' => 'checkbox',
        '#attributes' => ['id' => ['enablePosition'], 'checked' => ['checked']],
        '#title' => $this->t('Show mouse position'),
      ],
      [
        '#markup' => '<span id="hoverdata"></span>',
      ],
      [
        '#markup' => '<span id="clickdata"></span>',
      ],
    ];
    $text[] = $this->t('A tooltip is easy to build with a bit of jQuery code and the data returned from the plot.');

    $text[] = [
      [
        '#type' => 'checkbox',
        '#attributes' => ['id' => ['enableTooltip'], 'checked' => ['checked']],
        '#title' => $this->t('Enable tooltip'),
      ],
    ];
    $output[] = [
      '#data' => $data,
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#text' => $text,
      '#options' => $options,
      '#attached' => ['library' => ['flot_examples/interacting']],
    ];
    return $output;
  }

}
