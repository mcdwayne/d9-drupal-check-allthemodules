<?php

/**
 * @file
 * Contains \Drupal\station_playlist\Controller\PlaylistAddByProgramController.
 */

namespace Drupal\station_playlist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * @todo.
 */
class PlaylistAddByProgramController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function addByProgram(NodeTypeInterface $node_type, NodeInterface $station_program) {
    $node = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => $node_type->id(),
      'station_playlist_program' => $station_program->id(),
    ));

    return $this->entityFormBuilder()->getForm($node);
  }

}
