<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\Enum;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\EnumSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\EnumAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleCollectionAttribute;

/**
 * List type support.
 */
class ListType extends BaseField {

  /**
   * Gets schema field converted to CleverReach SchemaAttribute.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   *   CleverReach schema attribute.
   */
  public function getSchemaField() {
    $possibleValues = [];
    $options = $this->field->getFieldStorageDefinition()->getSetting('allowed_values');

    foreach ($options as $key => $label) {
      $possibleValues[] = new Enum($label, $key);
    }

    if ($this->isSingleValue()) {
      return new EnumSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        $possibleValues
      );
    }

    return new SimpleCollectionSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        [Conditions::CONTAINS],
        SchemaAttributeTypes::ENUM
    );
  }

  /**
   * Converts to search result object from Drupal's object base on type.
   *
   * @param \Drupal\node\Entity\Node|null $node
   *   Drupal content type object.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleAttribute
   *   CleverReach search result attribute.
   */
  public function getSearchResultValue($node) {
    $code = $this->field->getName();

    if ($this->isSingleValue()) {
      return new EnumAttribute($code, $node->get($code)->getString());
    }

    $attributes = [];
    foreach ($node->get($code)->getValue() as $value) {
      if (!isset($value['value'])) {
        continue;
      }

      $attributes[] = new EnumAttribute($code, $value['value']);
    }

    return new SimpleCollectionAttribute($code, $attributes);
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
    return [Conditions::EQUALS, Conditions::NOT_EQUAL];
  }

  /**
   * Indicates whether field is searchable or not.
   *
   * @return bool
   *   If field searchable, return true, otherwise false.
   */
  protected function isSearchable() {
    return TRUE;
  }

}
