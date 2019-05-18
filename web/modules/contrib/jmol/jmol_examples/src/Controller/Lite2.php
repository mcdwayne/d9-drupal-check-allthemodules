<?php

namespace Drupal\jmol_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart with minimal options.
 */
class Lite2 extends ControllerBase {

  /**
   * Content.
   */
  public function content() {

    $info = [
      'width' => 500,
      'height' => 500,
      'debug' => FALSE,
      'color' => "0xC0C0C0",
      'addSelectionOptions' => TRUE,
      'serverURL' => "http://chemapps.stolaf.edu/jmol/jsmol/php/jsmol.php",
      'use' => "HTML5",
      'readyFunction' => NULL,
      'defaultModel' => ":dopamine",
      'bondWidth' => 4,
      'zoomScaling' => 1.5,
      'pinchScaling' => 2.0,
      'mouseDragFactor' => 0.5,
      'touchDragFactor' => 0.15,
      'multipleBondSpacing' => 4,
      'spinRateX' => 0.2,
      'spinRateY' => 0.5,
      'spinFPS' => 20,
      'spin' => TRUE,
      'debug' => FALSE,
    ];

    $output[] = [
      '#type' => 'jmol',
      '#version' => 'lite',
      '#theme' => 'jmol_examples_lite2',
      '#info' => $info,
    ];

    return $output;
  }

}
