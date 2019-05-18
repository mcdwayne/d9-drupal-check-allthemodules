<?php

namespace Drupal\chessboard_images;

use Drupal\chessboard_lib\ericalvaresnl\chessboard_diagram\Diagram;

/**
 * Provides chessboard rendering.
 *
 * @internal
 */
trait ToImagesTrait {

  /**
   * @inheritdoc
   */
  protected function filter($value) {
    return [
      '#lazy_builder' => [
        [static::class, 'buildDiagram'],
        [$value['board_value'], $value['language_code'], $value['file_max'], $value['square_color_first'], $value['border']['T'], $value['border']['B'], $value['border']['L'], $value['border']['R']],
      ],
    ];
  }

  /**
   * Lazy builder callback.
   *
   * @param string $board_value
   * @param string $language_code
   * @param int $file_max
   * @param int $square_color_first
   * @param bool $border_t
   * @param bool $border_b
   * @param bool $border_l
   * @param bool $border_r
   *
   * @return array
   */
  public static function buildDiagram($board_value, $language_code, $file_max, $square_color_first, $border_t, $border_b, $border_l, $border_r) {
    $diagram = new Diagram($board_value, $file_max);
    $diagram->setSquareColorFirst($square_color_first)
      ->setLanguageCode($language_code)
      ->setBorder(['T' => $border_t, 'B' => $border_b, 'L' => $border_l, 'R' => $border_r]);
    return [
      'diagram' => [
        '#type' => 'chessboard_images_diagram',
        '#diagram' => $diagram,
      ],
    ];
  }

}
