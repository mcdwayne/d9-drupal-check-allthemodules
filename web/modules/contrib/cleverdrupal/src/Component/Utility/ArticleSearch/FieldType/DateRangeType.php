<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ObjectSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ComplexCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\DateAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ObjectAttribute;

/**
 * Date range type support.
 */
class DateRangeType extends DateType {

  /**
   * @inheritdoc
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
   * @inheritdoc
   */
  public function getSearchResultValue($node) {
    $code = $this->field->getName();

    $attributes = [];
    foreach ($node->get($code)->getValue() as $value) {
      if (!$startDate = $this->toDate($value['value'])) {
        continue;
      }

      if (!$endDate = $this->toDate($value['end_value'])) {
        continue;
      }

      $attributes[] = new ObjectAttribute(
        $code, [
          new DateAttribute('value', $startDate),
          new DateAttribute('end_value', $endDate),
        ]
      );
    }

    if ($this->isSingleValue()) {
      return isset($attributes[0]) ? $attributes[0] : new ObjectAttribute($code);
    }

    return new ComplexCollectionAttribute($code, $attributes);
  }

  /**
   * Gets list of sub attributes.
   *
   * @return array List of sub attributes.
   */
  private function getAttributes() {
    return [
      new SimpleSchemaAttribute(
            'value',
            t('Start Value'),
            FALSE,
            [],
            SchemaAttributeTypes::DATE
      ),
      new SimpleSchemaAttribute(
            'end_value',
            t('End Value'),
            FALSE,
            [],
            SchemaAttributeTypes::DATE
      ),
    ];
  }

}
