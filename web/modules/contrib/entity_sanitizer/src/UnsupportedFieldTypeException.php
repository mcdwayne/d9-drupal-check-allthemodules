<?php

namespace Drupal\entity_sanitizer;

class UnsupportedFieldTypeException extends \Exception {

  /**
   * The field type that caused this exception.
   *
   * @var string
   */
  private $fieldType;

  /**
   * The field that caused this exception.
   *
   * @var string
   */
  private $fieldName;

  /**
   * UnsupportedFieldTypeException constructor.
   *
   * @param string $field_type
   * @param int $field_name
   * @param int $code
   * @param \Exception|NULL $previous
   */
  public function __construct($field_type, $field_name, $code = 0, \Exception $previous = NULL) {
    $this->fieldType = $field_type;
    $this->fieldName = $field_name;
    parent::__construct("Unsupported sanitize field type {$field_type} for field {$field_name}.", $code, $previous);
  }

  /**
   * @return string
   *   The field type that caused this exception.
   */
  public function getFieldType() {
    return $this->fieldType;
  }

  /**
   * @return string
   *   The field that caused this exception.
   */
  public function getFieldName() {
    return $this->fieldName;
  }
}
