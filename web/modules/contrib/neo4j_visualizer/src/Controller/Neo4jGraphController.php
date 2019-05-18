<?php

namespace Drupal\neo4j_visualizer\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;

class Neo4JGraphController extends ControllerBase {

  public function form() {
    return $this->formBuilder()->getForm('Drupal\neo4j_visualizer\Form\GraphForm');
  }

}
