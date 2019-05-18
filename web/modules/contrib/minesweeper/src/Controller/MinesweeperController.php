<?php

/**
 * @file
 * Contains \Drupal\minesweeper\Controller\MinesweeperController.
 */

namespace Drupal\minesweeper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;

/**
 * Provides route responses for the Minesweeper module.
 */
class MinesweeperController extends ControllerBase {

  /**
   * Returns a simple page displaying the gametypes.
   *
   * @return array
   *   A simple renderable array.
   */
  public function gameTypeOverview() {
    $output['gametypes'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Pick a game type.'),
    );
    // Get all gametypes.
    $game_types = \Drupal::entityTypeManager()->getStorage('gametype')->loadMultiple();

    foreach ($game_types as $type) {
      $game_type = array(
        'title' => $type->label,
        'description' => $type->getDescription(),
        'name' => $type->id(),
      );
      $output['gametypes'][$game_type['name']] = array(
        '#theme' => 'minesweeper_game_type',
        '#game_type' => $game_type,
      );
    }
    return $output;
  }

}
