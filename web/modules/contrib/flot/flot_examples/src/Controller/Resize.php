<?php

namespace Drupal\flot_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart is is able to be resized by the user.
 */
class Resize extends ControllerBase {

  /**
   * Resize.
   */
  public function content() {
    // Basic line chart.
    $sin_data = array();
    for ($i = 0; $i < 14; $i += 0.5) {
      $sin_data[] = array($i, sin($i));
    }
    $series[] = array('data' => $sin_data);
    $series[] = array(
      'data' => array(array(0, 3), array(4, 8), array(8, 5), array(9, 13)),
    );
    $series[] = array(
      'data' => array(
        array(0, 12),
        array(7, 12),
        NULL,
        array(7, 2.5),
        array(12, 2.5),
      ),
    );
    $text = [];
    $text[] = [
      [
        '#markup' => '<p class="message"></p>',
      ],
    ];
    $text[] = $this->t('Sometimes it makes more sense to just let the plot take up the available space. In that case, we need to redraw the plot each time the placeholder changes its size. If you include the resize plugin, this is handled automatically.');

    $output[] = [
      '#type' => 'flot',
      '#data' => $series,
      '#theme' => 'flot_examples',
      '#resizable' => TRUE,
      '#text' => $text,
      '#attached' => ['library' => ['flot_examples/resize']],
    ];
    return $output;
  }

}
