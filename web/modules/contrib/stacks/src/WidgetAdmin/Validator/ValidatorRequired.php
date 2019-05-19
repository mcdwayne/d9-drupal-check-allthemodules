<?php

namespace Drupal\stacks\WidgetAdmin\Validator;

/**
 * Class ValidatorRequired.
 * @package Drupal\stacks\WidgetAdmin\Validator
 */
class ValidatorRequired extends BaseValidator {

  /**
   * @inheritDoc
   */
  public function validates($field_value) {
    return is_array($field_value) ? !empty(array_filter($field_value)) : !empty($field_value);
  }

}
