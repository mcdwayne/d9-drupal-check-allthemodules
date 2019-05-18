<?php

namespace Drupal\entity_resource_layer\Exception;

/**
 * Class EntityResourceFieldException.
 *
 * @package Drupal\entity_resource_layer\Exception
 */
class EntityResourceFieldException extends EntityResourceConstraintException {

  /**
   * The erroneous fields name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * EntityResourceFieldException constructor.
   *
   * @param string $message
   *   The message name.
   * @param string $fieldName
   *   The field name.
   * @param string|null $type
   *   The field exception type.
   */
  public function __construct($message, $fieldName, $type = NULL) {
    $this->fieldName = $fieldName;
    parent::__construct($message, $type);
  }

  /**
   * Get the erroneous fields name.
   *
   * @return string
   *   Field name.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Sets the field name.
   *
   * @param string $fieldName
   *   The new field name.
   */
  public function setFieldName($fieldName) {
    $this->fieldName = $fieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceData($includeCode = FALSE) {
    return parent::getResourceData(TRUE) + [
      'field' => $this->getFieldName()
    ];
  }

}
