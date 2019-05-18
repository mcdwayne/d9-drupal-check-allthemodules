<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

/**
 * Base field class.
 */
abstract class BaseField {
  /**
   * Field instance.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  protected $field;

  /**
   * BaseField constructor.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition|null $field
   *   Field instance.
   */
  public function __construct($field) {
    $this->field = $field;
  }

  /**
   * Gets schema field converted to CleverReach SchemaAttribute.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   *   CleverReach schema attribute.
   */
  abstract public function getSchemaField();

  /**
   * Converts to search result object from Drupal's object base on type.
   *
   * @param \Drupal\node\Entity\Node|null $node
   *   Drupal content type object.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleAttribute
   *   CleverReach search result attribute.
   */
  abstract public function getSearchResultValue($node);

  /**
   * Checks if passed field is single or multi value.
   *
   * @return bool
   *   Returns true if field is single value, otherwise if multi value field
   *   returns false.
   */
  protected function isSingleValue() {
    return $this->field->getFieldStorageDefinition()->getCardinality() === 1;
  }

  /**
   * Gets list of searchable conditions.
   *
   * @see \CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions
   *
   * @return array
   *   Gets supported searchable conditions.
   */
  protected function getSearchableConditions() {
    return [];
  }

  /**
   * Indicates whether field is searchable or not.
   *
   * @return bool
   *   If field searchable, return true, otherwise false.
   */
  protected function isSearchable() {
    return FALSE;
  }

}
