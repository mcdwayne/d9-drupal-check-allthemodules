<?php

namespace Drupal\formazing\FieldViewer\Parser;

abstract class Parser {

  /**
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $field
   * @return array
   */
  public static function parse($field) {
    /** @var \Drupal\formazing\FieldSettings\TextField $fieldType */
    $fieldType = $field->getFieldType();

    $render = [
      '#type' => $fieldType::getMachineTypeName(),
      '#default_value' => $field->getFieldValue(),
      '#description' => $field->getDescription(),
      '#required' => $field->isRequired(),
      '#prefix' => $field->getPrefix(),
      '#suffix' => $field->getSuffix(),
      '#attributes' => [
        'placeholder' => $field->getPlaceholder(),
      ]
    ];

    $field->isShowingLabel() ? $render['#title'] = $field->getName() : FALSE;

    return $render;
  }
}
