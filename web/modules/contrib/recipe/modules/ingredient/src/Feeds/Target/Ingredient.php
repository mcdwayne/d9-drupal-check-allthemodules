<?php

namespace Drupal\ingredient\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Feeds\Target\EntityReference;

/**
 * Defines an ingredient field mapper.
 *
 * @FeedsTarget(
 *   id = "ingredient",
 *   field_types = {"ingredient"},
 *   arguments = {"@entity.manager", "@entity.query"}
 * )
 */
class Ingredient extends EntityReference {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('target_id')
      ->addProperty('quantity')
      ->addProperty('unit_key')
      ->addProperty('note');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    foreach ($values as $column => $value) {
      switch ($column) {
        case 'target_id':
          $values[$column] = $this->getFile($value);
          break;

        default:
          $values[$column] = (string) $value;
          break;
      }
    }
  }

}
