<?php

namespace Drupal\chessboard_lib\ericalvaresnl\chessboard_tag;

/**
 * Provides chessboard tag processing.
 *
 * @internal
 */
Trait ChessboardTagTrait {

  private function parse($value) {
    preg_match('@^\s*(?:\((.*?)\))?(.*)$@s', $value, $matches);
    $item = [];

    // Parameter Parsing
    //--------------------------------
    $params =& $matches[1];

    // Number of files: any integer.
    if (preg_match('@[0-9]+@', $params, $m)) {
      $item['file_max'] = $m[0];
    }

    // Color of the upper left square: [l]ight or [d]ark.
    if (strpos($params, 'd') !== FALSE) {
      $item['square_color_first'] = 1;
    }
    elseif (strpos($params, 'l') !== FALSE) {
      $item['square_color_first'] = 0;
    }

    // Borders.
    $item['border'] = ['T' => (strpos($params, 'T') !== FALSE),
      'B' => (strpos($params, 'B') !== FALSE),
      'L' => (strpos($params, 'L') !== FALSE),
      'R' => (strpos($params, 'R') !== FALSE)];

    $item += $this->settings;

    // Render the board in XHTML syntax
    //----------------------------------
    $board_value =& $matches[2];
    $board_value = strip_tags($board_value);
    // Ignore unknown characters.
    $board_value = preg_replace('@[^-0-9KQBNRPACkqbnrpacx]@', '', $board_value);
    // Multiple empty squares.
    $replace_pairs = [];
    for ($i=10; $i >= 0; $i--) {
      $replace_pairs[$i] = str_repeat('-', $i);
    }
    $board_value = strtr($board_value, $replace_pairs);
    $item['board_value'] = $board_value;

    return $item;
  }

}
