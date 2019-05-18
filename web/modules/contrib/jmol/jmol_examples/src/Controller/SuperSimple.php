<?php

namespace Drupal\jmol_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart with minimal options.
 */
class SuperSimple extends ControllerBase {

  /**
   * Content.
   */
  public function content() {
    $info = [
      'width' => 400,
      'height' => 400,
      'debug' => FALSE,
      'color' => "0xC0C0C0",
      'disableJ2SLoadMonitor' => TRUE,
      'disableInitialConsole' => TRUE,
      'addSelectionOptions' => TRUE,
      'serverURL' => "http://chemapps.stolaf.edu/jmol/jsmol/php/jsmol.php",
      'use' => "HTML5",
      'readyFunction' => NULL,
      'script' => 'load $caffeine',
    ];

    $output[] = [
      '#type' => 'jmol',
      '#version' => 'full',
      '#info' => $info,
    ];

    return $output;
  }

}
