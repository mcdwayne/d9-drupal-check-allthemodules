<?php

namespace Drupal\chessboard\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * @RenderElement("chessboard_simple_diagram")
 */
class ChessboardSimpleDiagram extends RenderElement {

  public function getInfo() {
    $type = [
      '#diagram' => NULL,
      '#pre_render' => [
        [static::class, 'preRenderChessboardDiagram'],
      ],
    ];
    $type['#table_tiles'] = static::getTiles();
    $type['#theme'] = static::getThemeHook();
    return $type;
  }

  public static function preRenderChessboardDiagram($elements) {
    /** @var \Drupal\chessboard_lib\ericalvaresnl\chessboard_diagram\Diagram $diagram */
    $diagram = $elements['#diagram'];
    $elements['#border'] = $diagram->getBorder();
    $elements['#square_color_first'] = $diagram->getSquareColorFirst();
    $elements['#file_max'] = $diagram->getFileMax();
    $elements['#board'] = [];
    $size = $diagram->getSize();
    for ($i = 0; $i < $size; $i++) {
      $rank_d = (int) floor($i / $elements['#file_max']);
      $file = $i - $rank_d * $elements['#file_max'];
      if ($file == 0) {
        $elements['#board'][$rank_d] = [];
      }
      $elements['#board'][$rank_d][$file] = $diagram->getCode($rank_d, $file);
    }
    return $elements;
  }

  /**
   * Returns the theme hook name.
   *
   * @return string
   */
  protected static function getThemeHook() {
    return 'chessboard_diagram';
  }

  /**
   * Constructs the translation table.
   *
   * @return array
   */
  protected static function getTiles() {
    // Empty and marked squares
    $table_tiles = [
      '-0' => ['#plain_text' => '-'],
      '-1' => ['#plain_text' => '-'],
      'x0' => ['#plain_text' => 'x'],
      'x1' => ['#plain_text' => 'x'],
    ];

    // Pieces
    $pieces = 'kqbnrpac';
    for ($i=0; $i<8; $i++) {
      $piece = $pieces[$i];
      $table_tiles += array(
        strtoupper($piece) . '0' => ['#plain_text' => strtoupper($piece)],
        strtoupper($piece) . '1' => ['#plain_text' => strtoupper($piece)],
        $piece . '0' => ['#plain_text' => $piece],
        $piece . '1' => ['#plain_text' => $piece],
      );
    }

    // Borders
    $table_tiles += array(
      'T' => ['#plain_text' => '='],
      'B' => ['#plain_text' => '='],
      'L' => ['#plain_text' => '|'],
      'R' => ['#plain_text' => '|'],
      'TL' => ['#plain_text' => ' '],
      'TR' => ['#plain_text' => ' '],
      'BL' => ['#plain_text' => ' '],
      'BR' => ['#plain_text' => ' '],
    );
    return $table_tiles;
  }

}
