<?php

namespace Drupal\chessboard_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'chessboard' widget.
 *
 * @FieldWidget(
 *   id = "chessboard_default",
 *   label = @Translation("Chessboard"),
 *   field_types = {
 *     "chessboard"
 *   }
 * )
 */
class ChessboardWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['piece_placement'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->piece_placement) ? $items[$delta]->piece_placement : NULL,
      '#size' => 64,
      '#maxlength' => 64,
    );

    return $element;
  }

}
