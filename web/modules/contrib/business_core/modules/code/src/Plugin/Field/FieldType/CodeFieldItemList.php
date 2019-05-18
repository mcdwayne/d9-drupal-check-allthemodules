<?php

namespace Drupal\code\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;

/**
 * Represents a configurable entity code field.
 */
class CodeFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);

    if (!$default_value) {
      if (!empty($encoding_rules = $definition->getSetting('encoding_rules'))) {
        $default_value = \Drupal::token()->replace($encoding_rules, [
          $entity->getEntityTypeId() => $entity,
          'entity_type_id' => $entity->getEntityTypeId(),
          'bundle' => $entity->bundle(),
          'field_name' => $definition->getName(),
          'encoding_rules' => $encoding_rules,
        ]);
      }
    }

    return $default_value;
  }

}
