<?php

namespace Drupal\jmol_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart with minimal options.
 */
class Lite4 extends ControllerBase {

  /**
   * Content.
   */
  public function content() {
    $info = [
      'width' => '500',
      'height' => '500',
      'color' => "0xC0C0C0",
      'addSelectionOptions' => TRUE,
      'serverURL' => "http://chemapps.stolaf.edu/jmol/jsmol/php/jsmol.php",
      'use' => "HTML5",
      'defaultModel' => ":dopamine",
      'spin' => TRUE,
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
