<?php

namespace Drupal\chessboard_images\Element;

use Drupal\chessboard\Element\ChessboardSimpleDiagram;

/**
 * @RenderElement("chessboard_images_diagram")
 */
class ChessboardImagesDiagram extends ChessboardSimpleDiagram {

  /**
   * Returns the theme hook name.
   *
   * @return string
   */
  protected static function getThemeHook() {
    return 'chessboard_images_diagram';
  }

  /**
   * Constructs the translation table.
   *
   * @return array
   */
  protected static function getTiles() {
    $image_directory_path = drupal_get_path('module', 'chessboard_images') . '/default';
    $dimensions = array(
      'square' => array(
        'width' => 40,
        'height' => 40,
      ),
      'border-h' => array(
        'width' => 40,
        'height' => 4,
      ),
      'border-v' => array(
        'width' => 4,
        'height' => 40,
      ),
      'border-c' => array(
        'width' => 4,
        'height' => 4,
      ),
    );
    $size_attributes = $dimensions;

    // Empty and marked squares
    $table_tiles = array(
      '-0' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename('-', 0), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => '-', '#title' => ''),
      '-1' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename('-', 1), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => '-', '#title' => ''),
      'x0' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename('x', 0), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => 'x', '#title' => ''),
      'x1' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename('x', 1), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => 'x', '#title' => ''),
    );

    // Pieces
    $pieces = 'kqbnrpac';
    for ($i=0; $i<8; $i++) {
      $piece = $pieces[$i];
      $table_tiles += array(
        strtoupper($piece) . '0' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename(strtoupper($piece), 0), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => strtoupper($piece), '#title' => ''),
        strtoupper($piece) . '1' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename(strtoupper($piece), 1), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => strtoupper($piece), '#title' => ''),
        $piece . '0' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename($piece, 0), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => $piece, '#title' => ''),
        $piece . '1' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::pieceFilename($piece, 1), '#width' => $size_attributes['square']['width'], '#height' => $size_attributes['square']['height'], '#alt' => $piece, '#title' => ''),
      );
    }

    // Borders
    $table_tiles += array(
      'T' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('T'), '#width' => $size_attributes['border-h']['width'], '#height' => $size_attributes['border-h']['height'], '#title' => ''),
      'B' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('B'), '#width' => $size_attributes['border-h']['width'], '#height' => $size_attributes['border-h']['height'], '#title' => ''),
      'L' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('L'), '#width' => $size_attributes['border-v']['width'], '#height' => $size_attributes['border-v']['height'], '#title' => ''),
      'R' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('R'), '#width' => $size_attributes['border-v']['width'], '#height' => $size_attributes['border-v']['height'], '#title' => ''),
      'TL' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('TL'), '#width' => $size_attributes['border-c']['width'], '#height' => $size_attributes['border-c']['height'], '#title' => ''),
      'TR' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('TR'), '#width' => $size_attributes['border-c']['width'], '#height' => $size_attributes['border-c']['height'], '#title' => ''),
      'BL' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('BL'), '#width' => $size_attributes['border-c']['width'], '#height' => $size_attributes['border-c']['height'], '#title' => ''),
      'BR' => array('#theme' => 'image', '#uri' => $image_directory_path . '/' . static::borderFilename('BR'), '#width' => $size_attributes['border-c']['width'], '#height' => $size_attributes['border-c']['height'], '#title' => ''),
    );
    return $table_tiles;
  }

  protected static function borderFilename($border) {
    // $border: T, B, L, R, TL, TR, BL, BR
    switch ($border) {
      case 'T':
      case 'B':
        return 'h.png';
      case 'L':
      case 'R':
        return 'v.png';
      default:
        return 'c.png';
    }
  }

  protected static function pieceFilename($piece, $square_color) {
    // $piece        : KQBNRPkqbnrp-
    // $square_color : 0 1
    switch ($piece) {
      case 'x':
        $name = 'xx';
        break;
      case '-':
        $name = '';
        break;
      default:
        $name = strtolower($piece) . (ctype_lower($piece) ? 'd' : 'l');
        break;
    }

    return $name . ($square_color ? 'd' : 'l') . '.png';
  }

}
