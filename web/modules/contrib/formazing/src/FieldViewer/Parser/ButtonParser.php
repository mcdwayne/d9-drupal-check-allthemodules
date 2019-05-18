<?php

namespace Drupal\formazing\FieldViewer\Parser;

class ButtonParser extends Parser {

  /**
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $field
   * @return array
   */
  public static function parse($field) {
    /** @var \Drupal\formazing\FieldSettings\TextField $fieldType */
    $fieldType = $field->getFieldType();

    return [
      '#type' => $fieldType::getMachineTypeName(),
      '#value' => $field->getName(),
      '#prefix' => $field->getPrefix(),
      '#suffix' => $field->getSuffix(),
    ];
  }
}