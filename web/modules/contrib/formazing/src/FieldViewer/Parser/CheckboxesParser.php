<?php

namespace Drupal\formazing\FieldViewer\Parser;

use Drupal\formazing\FieldHelper\FieldAction;

class CheckboxesParser extends Parser {

  /**
   * @param \Drupal\formazing\Entity\FieldFormazingEntity $field
   * @return array
   */
  public static function parse($field) {
    /** @var \Drupal\formazing\FieldSettings\TextField $fieldType */
    $fieldType = $field->getFieldType();
    $options = $field->get('field_options')->getValue();
    $options = FieldAction::filterEmptyOption($options);
    $options = array_map(function ($value) {
      return $value['value'];
    }, $options);

    $render = [
      '#type' => $fieldType::getMachineTypeName(),
      '#options' => $options,
      '#required' => $field->isRequired(),
      '#description' => $field->getDescription(),
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
