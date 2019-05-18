<?php

namespace Drupal\chessboard;

use Drupal\Core\TypedData\DataDefinition;

/**
 * Class PiecePlacementDataDefinition
 *
 * @internal
 */
class PiecePlacementDataDefinition extends DataDefinition {

 /**
   * Creates a new chessboard piece placement definition.
   *
   * @return static
   */
  public static function create($type = 'string') {
    if ($type !== 'string') {
      throw new \InvalidArgumentException('Unsupported data type.');
    }
    $definition['type'] = $type;
    $definition['constraints'] = array(
      'Length' => array(
        'min' => 64,
        'max' => 64,
      ),
      'ChessboardRegex' => array(
        'pattern' => '@[^-KQBNRPkqbnrp]@',
        'match' => FALSE,
      ),
    );

    return new static($definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDataType($type) {
    if ($type !== 'string') {
      throw new \InvalidArgumentException('Unsupported data type.');
    }
    return self::create($type);
  }

}
