<?php

namespace Drupal\jmol_examples\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Display a chart with minimal options.
 */
class JsmolExample extends ControllerBase {

  /**
   * Content.
   */
  public function content() {
    $info = [
      'width' => 250,
      'height' => 250,
      'debug' => FALSE,
      'color' => "0xC0C0C0",
      'disableJ2SLoadMonitor' => TRUE,
      'disableInitialConsole' => TRUE,
      'use' => "HTML5",
      'script' => 'load $CCCCCc1cc(c2c(c1)OC([C@H]3[C@H]2C=C(CC3)C)(C)C)O',
    ];
    $output[] = [
      '#type' => 'jmol',
      '#version' => 'full',
      '#info' => $info,
    ];
    return $output;
  }

}
