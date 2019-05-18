<?php

namespace Drupal\readmore_ajax\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;

class ReadMoreController extends ControllerBase {

  public function loadContent($nid = NULL) {

    // Get NID render.
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    $node_view = render(\Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view($node, 'full'));

    // New response.
    $response = new AjaxResponse();

    // Commands Ajax.
    $selector = '.node--view-mode-teaser[data-history-node-id="' . $nid . '"]';
    $response->addCommand(new ReplaceCommand($selector, $node_view, $settings = NULL));

    return $response;

  }

}