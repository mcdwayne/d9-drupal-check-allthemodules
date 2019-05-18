<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

/**
 * Base field class.
 */
abstract class BaseField {
  /**
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  protected $field;

  /**
   * BaseField constructor.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $field
   */
  public function __construct($field) {
    $this->field = $field;
  }

  /**
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   */
  abstract public function getSchemaField();

  /**
   * @param \Drupal\node\Entity\Node $node
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleAttribute
   */
  abstract public function getSearchResultValue($node);

  /**
   * @return bool Returns true if field is single value, otherwise if multi value field returns false.
   */
  protected function isSingleValue() {
    return $this->field->getFieldStorageDefinition()->getCardinality() === 1;
  }

  /**
   * Gets list of searchable conditions.
   *
   * @see \CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions
   *
   * @return array Gets supported searchable conditions.
   */
  protected function getSearchableConditions() {
    return [];
  }

  /**
   * Indicates whether field is searchable or not.
   *
   * @return bool If field searchable, return true, otherwise false.
   */
  protected function isSearchable() {
    return FALSE;
  }

}
