<?php

namespace Drupal\feeds_stock\Feeds\Target;

use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;

/**
 * Defines an integer field mapper.
 *
 * @FeedsTarget(
 *   id = "commerce_stock_level",
 *   field_types = {
 *     "commerce_stock_level",
 *   }
 * )
 */
class StockLevel extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $value = trim($values['stock']);
    $values['stock'] = [];
    $values['stock']['value'] = is_numeric($value) ? (int) $value : '';
    $values['stock']['entry_system'] = 'simple';
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
      return FieldTargetDefinition::createFromFieldDefinition($field_definition)
          ->addProperty('stock');
  }

}
