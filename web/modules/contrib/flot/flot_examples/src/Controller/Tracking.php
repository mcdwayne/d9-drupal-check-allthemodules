<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Demonstrate tracking values with the cursor.
 */
class Tracking extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {

    $cos = [];
    $sin = [];

    for ($i = 0; $i < 14; $i += .1) {
      $sin[] = [$i, sin($i)];
      $cos[] = [$i, cos($i)];
    }

    $data = [
      [
        'data' => $sin,
        'label' => 'sin(x) = -0.00',
      ],
      [
        'data' => $cos,
        'label' => 'cos(x) = -0.00',
      ],
    ];

    $options = [
      'series' => ['lines' => ['show' => TRUE]],
      'crosshair' => ['mode' => "x"],
      'grid' => [
        'hoverable' => TRUE,
        'autoHighlight' => FALSE,
      ],
      'yaxis' => [
        'min' => -1.2,
        'max' => 1.2,
      ],
    ];
    $text = [];
    $text[] = $this->t("You can add crosshairs that'll track the mouse position, either on both axes or as here on only one.");
    $text[] = $this->t('If you combine it with listening on hover events, you can use it to track the intersection on the curves by interpolating the data points (look at the legend).');
    $text[] = [
      ['#markup' => '<p id="hoverdata"></p>'],
    ];

    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#options' => $options,
      '#attached' => ['library' => ['flot_examples/tracking']],
      '#text' => $text,
    ];
    return $output;
  }

}
