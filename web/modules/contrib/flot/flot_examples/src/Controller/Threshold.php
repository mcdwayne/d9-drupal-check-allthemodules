<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Demonstrate highlighting data at a certain threshold.
 */
class Threshold extends ControllerBase {

  /**
   * Zooming.
   */
  public function content() {
    $d1 = array();
    for ($i = 0; $i <= 60; $i++) {
      $d1[] = [$i, round(rand() / getrandmax() * 30 - 10, 0)];
    }

    $data[] = [
      'data' => $d1,
      'color' => 'rgb(30, 180, 20)',
      'threshold' => [
        'below' => 0,
        'color' => 'rgb(200, 20, 30)',
      ],
      'lines' => ['steps' => TRUE],
    ];
    $text = [];
    $text[] = $this->t("With the threshold plugin, you can apply a specific color to the part of a data series below a threshold. This is can be useful for highlighting negative values, e.g. when displaying net results or what's in stock.");
    $text[] = [
      [
        '#markup' => '<p class="controls">',
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Threshold at 5'),
        '#attributes' => ['id' => ['T5']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Threshold at 0'),
        '#attributes' => ['id' => ['T0']],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Threshold at -2.5'),
        '#attributes' => ['id' => ['T-2.5']],
      ],
      [
        '#markup' => '</p>',
      ],
    ];
    $output[] = [
      '#type' => 'flot',
      '#theme' => 'flot_examples',
      '#data' => $data,
      '#attached' => ['library' => ['flot_examples/threshold']],
      '#text' => $text,
    ];
    return $output;
  }

}
