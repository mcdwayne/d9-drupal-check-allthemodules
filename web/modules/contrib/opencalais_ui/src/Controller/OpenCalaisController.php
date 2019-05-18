<?php

namespace Drupal\opencalais_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Returns responses for Node Revision routes.
 */
class OpenCalaisController extends ControllerBase {

  /**
   * Returns a table which shows the differences between two node revisions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node_type whose revisions are compared.
   *
   * @return array
   *   Table showing the diff between the two node revisions.
   */
  public function calaisTags(NodeInterface $node) {
    return $this->formBuilder()->getForm('Drupal\opencalais_ui\Form\TagsForm', $node);
  }

}
