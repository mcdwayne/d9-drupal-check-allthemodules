<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Controller\SettingsController.
 */

namespace Drupal\nodeletter\Controller;

use \Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\node\NodeTypeInterface;

class SettingsController extends ControllerBase {


  public function nodeTypeSettingsTitle( NodeTypeInterface $node_type) {

    return $this->t(
      "Manage Nodeletter setup for %node_type",
      ['%node_type' => $node_type->label()]
    );

  }

  public function nodeTypeSettings( NodeTypeInterface $node_type) {
    $build = [];
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\nodeletter\Form\NodeTypeForm', $node_type);
    return $build;
  }
}
