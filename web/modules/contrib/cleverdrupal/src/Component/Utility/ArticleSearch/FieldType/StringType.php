<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

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
   * @inheritdoc
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
   * @inheritdoc
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
   * @inheritdoc
   */
  protected function getSearchableConditions() {
    return [
      Conditions::EQUALS,
      Conditions::NOT_EQUAL,
      Conditions::CONTAINS,
    ];
  }

  /**
   * @inheritdoc
   */
  protected function isSearchable() {
    return TRUE;
  }

}
