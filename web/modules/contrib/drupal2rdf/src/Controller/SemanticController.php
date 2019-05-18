<?php

namespace Drupal\semantic_map\Controller;

use Drupal\Core\Controller\ControllerBase;

class SemanticController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
    ];
  }

  public function menu(){
    return [
      '#markup' => '<h2>' . $this->t('Semantic Map settings') . '</h2>',
    ];
  }

}
