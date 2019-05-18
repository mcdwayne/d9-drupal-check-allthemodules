<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

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
   * @inheritdoc
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
   * @inheritdoc
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
   * @inheritdoc
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
   * @inheritdoc
   */
  protected function isSearchable() {
    return TRUE;
  }

  /**
   * Converts timestamp to datetime object.
   *
   * @param int $value
   *
   * @return \DateTime Date time object.
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
