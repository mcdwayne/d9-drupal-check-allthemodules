<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ObjectSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ComplexCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\HtmlAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ObjectAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;

/**
 * TextWithSummary type support.
 */
class TextWithSummaryType extends BaseField {

  /**
   * Gets schema field converted to CleverReach SchemaAttribute.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   *   CleverReach schema attribute.
   */
  public function getSchemaField() {
    if ($this->isSingleValue()) {
      return new ObjectSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        $this->getAttributes()
      );
    }

    return new ComplexCollectionSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        $this->getAttributes()
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
    $values = $node->get($code)->getValue();

    $attributes = [];
    foreach ($values as $value) {
      $attributes[] = new ObjectAttribute(
        $code, [
          new HtmlAttribute('value', $value['value']),
          new TextAttribute('summary', $value['summary']),
        ]
      );
    }

    if ($this->isSingleValue()) {
      return isset($attributes[0]) ? $attributes[0] : new ObjectAttribute($code);
    }

    return new ComplexCollectionAttribute($code, $attributes);
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

  /**
   * Gets list of sub attributes.
   *
   * @return array
   *   List of sub attributes.
   */
  private function getAttributes() {
    return [
      new SimpleSchemaAttribute(
            'value',
            t('Value'),
            FALSE,
            [],
            SchemaAttributeTypes::HTML
      ),
      new SimpleSchemaAttribute(
            'summary',
            t('Summary'),
            FALSE,
            [],
            SchemaAttributeTypes::TEXT
      ),
    ];
  }

}
