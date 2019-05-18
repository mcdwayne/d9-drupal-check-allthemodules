<?php

namespace Drupal\chessboard\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\chessboard\ToPlainTextTrait;

/**
 * Plugin implementation of the 'chessboard_plain_text_formatter_diagram' formatter.
 *
 * @FieldFormatter(
 *   id = "chessboard_plain_text_formatter_diagram",
 *   label = @Translation("Chessboard plain text"),
 *   field_types = {
 *     "chessboard"
 *   }
 * )
 */
class ChessboardPlainTextFormatter extends FormatterBase {

  use ToPlainTextTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      $value = [
        'board_value' => $item->get('piece_placement')->getString(),
        'file_max' => 8,
        'square_color_first' => 0,
        'border' => [
          'T' => FALSE,
          'B' => FALSE,
          'L' => FALSE,
          'R' => FALSE,
        ],
        'language_code' => $langcode,
      ];
      $elements[$delta] = $this->filter($value);
    }

    return $elements;
  }

}
