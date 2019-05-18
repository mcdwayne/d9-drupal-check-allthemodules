<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Controller\NodeController.
 */

namespace Drupal\nodeletter\Controller;

use \Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

class NodeController extends ControllerBase {

  public function title(NodeInterface $node) {

    return $this->t(
      'Newsletter: @node_title',
      ['@node_title' => $node->getTitle()],
      ['context' => 'Nodeletter submit title']
    );

  }

  public function content(NodeInterface $node) {

    $build = [];

//    $title = $this->t(
//      'Newsletter: %node_title',
//      ['%node_title' => $node->getTitle()],
//      ['context' => 'Nodeletter submit title']
//    );
//    $build['node_title'] = [
//      '#markup' => "<h1>$title</h1>",
//    ];

    $build['submit'] = \Drupal::formBuilder()->getForm('Drupal\nodeletter\Form\NewsletterSubmitForm', $node);

    return $build;

  }

}
