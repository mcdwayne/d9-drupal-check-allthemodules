<?php

namespace Drupal\chessboard_field\Plugin\Field\FieldType;

use Drupal\chessboard\PiecePlacementDataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'chessboard' entity field type.
 *
 * @FieldType(
 *   id = "chessboard",
 *   label = @Translation("Chessboard"),
 *   description = @Translation("An entity field containing a chessboard."),
 *   default_widget = "chessboard_default",
 *   default_formatter = "chessboard_simple"
 * )
 */
class ChessboardItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['piece_placement'] = PiecePlacementDataDefinition::create()
      ->setLabel(t('Piece placement value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'piece_placement' => array(
          'type' => 'char',
          'length' => 64,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('piece_placement')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'piece_placement';
  }

}
