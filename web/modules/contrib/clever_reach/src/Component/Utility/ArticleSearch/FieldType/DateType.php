<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\DateAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleCollectionAttribute;
use DateTime;

/**
 * Date type support.
 */
class DateType extends BaseField {

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
        SchemaAttributeTypes::DATE
      );
    }

    return new SimpleCollectionSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        FALSE,
        [],
        SchemaAttributeTypes::DATE
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
      return new DateAttribute($code, $this->toDate($node->get($code)->getString()));
    }

    $attributes = [];
    foreach ($node->get($code)->getValue() as $value) {
      if ($date = $this->toDate($value['value'])) {
        $attributes[] = new DateAttribute($code, $date);
      }
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
      Conditions::GREATER_THAN,
      Conditions::GREATER_EQUAL,
      Conditions::LESS_THAN,
      Conditions::LESS_EQUAL,
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
   * Converts timestamp to datetime object.
   *
   * @param int $value
   *   Unix timestamp.
   *
   * @return \DateTime
   *   Date time object.
   */
  protected function toDate($value) {
    if (is_numeric($value)) {
      return (new DateTime())->setTimestamp($value);
    }

    if (strpos($value, 'T') === FALSE) {
      return DateTime::createFromFormat('Y-m-d', $value);
    }

    return DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
  }

}
