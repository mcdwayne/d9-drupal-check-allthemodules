<?php

namespace Drupal\formazing\FieldViewer\Parser;

class DateFieldParser extends Parser {

  /**
   * @inheritdoc
   */
  public static function parse($field) {
    /** @var \Drupal\formazing\FieldSettings\DateField $fieldType */
    $fieldType = $field->getFieldType();

    $render = [
      '#type' => $fieldType::getMachineTypeName(),
      '#default_value' => $field->getFieldValue(),
      '#description' => $field->getDescription(),
      '#required' => $field->isRequired(),
      '#prefix' => $field->getPrefix(),
      '#suffix' => $field->getSuffix(),
    ];

    $field->isShowingLabel() ? $render['#title'] = $field->getName() : FALSE;

    return $render;
  }
}
