<?php

/**
 * @file
 * Contains \Drupal\minesweeper\Controller\MinesweeperGameController.
 */

namespace Drupal\minesweeper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\minesweeper\Entity\Gametype;
use Drupal\minesweeper\Entity\Difficulty;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the Minesweeper module.
 */
class MinesweeperGameController extends ControllerBase {

  /**
   * Returns the game page.
   *
   * @param Gametype $gametype
   *   Config entity gametype
   *
   * @param Difficulty $difficulty
   *   Config entity containing width, height and amount of mines
   *
   * @return array
   *   A simple renderable array.
   */
  public function gamePage(Gametype $gametype, Difficulty $difficulty) {
    if (!in_array($difficulty->id(), $gametype->getAllowedDifficulties())) {
      throw new NotFoundHttpException();
    }

    $gameboard = $this->minesweeperGenerateEmptyBoard($difficulty);

    $header_data = array(
      'flags' => array(
        '#markup' => $this->t('Flags:'),
        '#suffix' => ' <span class="flag-counter">0</span><span> / ' . $difficulty->getMines() . '</span>',
      ),
      'time_counter' => array(
        '#markup' => '<div class="time-counter">0:00</div>',
      ),
    );

    $header = array(
      array(
        'data' => $header_data,
        'colspan' => $difficulty->getBoardWidth(),
      ),
    );

    $rows = array();
    foreach ($gameboard as $tile) {
      $y = (int) floor($tile['id'] / $difficulty->getBoardWidth());
      $rows[$y][] = array(
        'data' => '',
        'class' => array('tile', 'tile-' . $tile['id']),
        'id' => $tile['id'],
      );
    }

    $output['minefield'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => array(
        'class' => array('minesweeper', $difficulty->id()),
      ),
      '#rows' => $rows,
    );
    $output['minefield']['#attached']['library'][] = 'minesweeper/minesweeper_gameboard_css';
    $output['minefield']['#attached']['library'][] = 'minesweeper/minesweeper_gameboard_js';
    $output['minefield']['#attached']['library'][] = 'minesweeper/minesweeper_js_' . $gametype->id();

    if (!empty($gametype->getMultiplayer())) {
      $output['minefield']['#attached']['library'][] = 'minesweeper/togetherjs';
    }
    $output['minefield']['#attached']['drupalSettings']['minesweeper'] = [
      'difficulty' => $difficulty->id(),
      'gametype' => $gametype->id(),
    ];

    return $output;
  }

  /**
   * Generate an empty minefield board based on the chosen setup.
   *
   * @param Difficulty $difficulty
   *   Config entity containing width, height and amount of mines
   *
   * @return array
   *   Array of tiles with every neighbour defined
   */
  private function minesweeperGenerateEmptyBoard(Difficulty $difficulty) {
    $max = ($difficulty->getBoardWidth() * $difficulty->getBoardHeight());

    $board = array();
    $id = 0;
    while (count($board) < $max) {
      $board[$id] = array(
        'id' => $id,
        'neighbours' => $this->minesweeperGetNeighbours($difficulty, $id),
      );
      $id++;
    }

    return $board;
  }

  /**
   * Define all neighbours for a given tile in a given setup.
   *
   * @param Difficulty $difficulty
   *   Config entity containing width, height and amount of mines
   * @param int $id
   *   Tile id
   *
   * @return array
   *   Neighbours for a given tile
   */
  private function minesweeperGetNeighbours(Difficulty $difficulty, $id) {
    $board_width = $difficulty->getBoardWidth();
    $x = $id % $board_width;
    $y = floor($id / $board_width);

    $neighbours = array(
      'left' => $id - 1,
      'right' => $id + 1,
      'up' => $id - $board_width,
      'down' => $id + $board_width,
      'up_left' => $id - $board_width - 1,
      'up_right' => $id - $board_width + 1,
      'down_left' => $id + $board_width - 1,
      'down_right' => $id + $board_width + 1,
    );

    // If the tile is in the left column, remove all left neighbours.
    if ($x == 0) {
      unset($neighbours['left']);
      unset($neighbours['up_left']);
      unset($neighbours['down_left']);
    }

    // If the tile is in the right column, remove all right neighbours.
    if ($x == ($board_width - 1)) {
      unset($neighbours['right']);
      unset($neighbours['up_right']);
      unset($neighbours['down_right']);
    }

    // If the tile is in the top row, remove al upper neighbours.
    if ($y == 0) {
      unset($neighbours['up']);
      unset($neighbours['up_left']);
      unset($neighbours['up_right']);
    }

    // If the tile is in the bottom row, remove all lower neighbours.
    if ($y == ($difficulty->getBoardHeight() - 1)) {
      unset($neighbours['down']);
      unset($neighbours['down_left']);
      unset($neighbours['down_right']);
    }

    return $neighbours;
  }

  /**
   * Counts the amount of mines for a given tile.
   *
   * Counts the amount of mines among direct neighbours for a given tile in a
   * given board.
   *
   * @param array $tile
   *   Array containing information about the tile.
   * @param array $board
   *   Array containing the complete gameboard.
   *
   * @return int
   *   Number of mines among direct neighbours.
   */
  private function minesweeperGetNumber($tile, $board) {
    $count = 0;
    foreach ($tile['neighbours'] as $id) {
      if (isset($board[$id]['mine']) && $board[$id]['mine']) {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Generate all mines.
   *
   * @param array $setup
   *   Array containing:
   *     - dimensions
   *     - amount of mines
   *     - an empty board based on the dimensions
   * @param int $start
   *   Int Id of the first clicked tile
   *
   * @return array
   *   List of tile ids that contain bombs
   */
  private function minesweeperGenerateMines($setup, $start) {
    $mines = array();

    // Make sure the first tile is empty by excluding it and its neighbours.
    $exclude = array_values($setup['board'][$start]['neighbours']);
    $exclude[] = $start;

    while (count($mines) < $setup['mines']) {
      $new_mine = $this->minesweeperCreateMine($setup, $exclude);

      $mines[] = $new_mine;
      $exclude[] = $new_mine;
    }

    return $mines;
  }

  /**
   * Create a single mine in a random tile that's not excluded.
   *
   * @TODO: This will hit a recursion limit with certain setups. The current
   * available setups work fine, but providing a custom setup could become
   * problematic right now. (Especially with boards heavy on mines 50%+)
   *
   * @param array $setup
   *   Array containing:
   *     - dimensions
   *     - amount of mines
   *     - an empty board based on the dimensions
   * @param array $excluded
   *   Array containing tiles that should not get a mine.
   *
   * @return int
   *   The position of the mine on the board.
   */
  private function minesweeperCreateMine($setup, $excluded) {
    $max = ($setup['width'] * $setup['height']) - 1;
    $mine = mt_rand(0, $max);
    if (in_array($mine, $excluded)) {
      $mine = $this->minesweeperCreateMine($setup, $excluded);
    }
    return $mine;
  }

  /**
   * Returns the JSON after the user clicks the first file.
   *
   * @param Gametype $gametype
   *   Config entity gametype
   * @param Difficulty $difficulty
   *   Config entity containing width, height and amount of mines
   * @param int $first_move
   *   The id of the clicked tile.
   *
   * @return JsonResponse
   *   The complete board as JSON
   */
  public function gameStart(Gametype $gametype, Difficulty $difficulty, $first_move) {
    // Make sure the move is legit.
    if (!is_numeric($first_move) ||
      (is_numeric($first_move) && $first_move < 0) ||
      (is_numeric($first_move) && $first_move > ($difficulty->getBoardWidth() * $difficulty->getBoardHeight() - 1))) {
      throw new NotFoundHttpException();
    }

    // Prepare the board.
    $board = $this->minesweeperGenerateEmptyBoard($difficulty);
    $setup = array(
      'width' => $difficulty->getBoardWidth(),
      'height' => $difficulty->getBoardHeight(),
      'mines' => $difficulty->getMines(),
    );
    $setup['board'] = $board;

    // Generate the mines.
    $mines = $this->minesweeperGenerateMines($setup, $first_move);
    foreach ($mines as $mine) {
      $board[$mine]['mine'] = TRUE;
    }

    // Add numbers.
    foreach ($board as $tile) {
      if (!isset($tile['mine'])) {
        $board[$tile['id']]['value'] = $this->minesweeperGetNumber($tile, $board);
      }
      if (isset($tile['mine']) && $tile['mine']) {
        $board[$tile['id']]['value'] = 'M';
      }
    }

    // Return the board.
    $minesweeper_board = array(
      'gametype' => $gametype->id(),
      'difficulty' => $difficulty->id(),
      'board' => $board,
    );

    return new JsonResponse($minesweeper_board);
  }
}
