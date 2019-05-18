<?php

namespace Drupal\chessboard_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'chessboard simple' formatter.
 *
 * @FieldFormatter(
 *   id = "chessboard_simple",
 *   label = @Translation("Chessboard simple"),
 *   field_types = {
 *     "chessboard"
 *   }
 * )
 */
class ChessboardSimpleFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#prefix' => '<pre>',
        '#plain_text' => $item->get('piece_placement')->getString(),
        '#suffix' => '</pre>',
      ];
    }

    return $elements;
  }

}
