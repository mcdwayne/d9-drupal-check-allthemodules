<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;

/**
 * String type support.
 */
class StringType extends BaseField {

  /**
   * Gets schema field converted to CleverReach SchemaAttribute.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   *   CleverReach schema attribute.
   */
  public function getSchemaField() {
    if ($this->isSingleValue()) {
      return new SimpleSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        SchemaAttributeTypes::TEXT
      );
    }

    return new SimpleCollectionSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        [Conditions::CONTAINS],
        SchemaAttributeTypes::TEXT
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
      return new TextAttribute($code, $node->get($code)->getString());
    }

    $attributes = [];
    foreach ($node->get($code)->getValue() as $value) {
      if (!isset($value['value'])) {
        continue;
      }

      $attributes[] = new TextAttribute($code, $value['value']);
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
    return [
      Conditions::EQUALS,
      Conditions::NOT_EQUAL,
      Conditions::CONTAINS,
    ];
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
