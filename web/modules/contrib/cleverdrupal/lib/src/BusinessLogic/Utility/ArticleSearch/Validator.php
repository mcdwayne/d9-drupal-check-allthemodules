<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult;
use CleverReach\Infrastructure\Logger\Logger;

/**
 * Class Validator, validation of generated filters and search result.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch
 */
class Validator {
  const SEARCHABLE_ITEM_CODE_FIELD_NAME = 'itemCode';
  const SEARCHABLE_ITEM_ID_FIELD_NAME = 'id';

  /**
   * Validates generated filters based on item schema.
   *
   * @param array $filters
   * @param \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema $itemSchema
   *
   * @throws InvalidSchemaMatching
   */
  public function validateFilters(array $filters, SearchableItemSchema $itemSchema) {
    $flatSchemaAttributeMap = $this->generateFlatItemSchemaAttributes($itemSchema->getAttributes());

    /** @var Filter $filter */
    foreach ($filters as $filter) {
      if (!in_array($filter->getAttributeCode(), [
        self::SEARCHABLE_ITEM_CODE_FIELD_NAME,
        self::SEARCHABLE_ITEM_ID_FIELD_NAME,
      ]
        ) &&
        !$this->isFilterMatchingTheSchema($flatSchemaAttributeMap, $filter)
      ) {
        $errorMessage = $filter->getAttributeCode() . ' does not match given schema.';
        Logger::logError($errorMessage);
        throw new InvalidSchemaMatching($errorMessage);
      }
    }
  }

  /**
   * Validates search result based on item schema.
   *
   * @param \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult $searchResult
   * @param \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema $itemSchema
   *
   * @throws InvalidSchemaMatching
   */
  public function validateSearchResults(SearchResult $searchResult, SearchableItemSchema $itemSchema) {
    $flatSchemaAttributeCodes = $this->generateFlatItemSchemaAttributes($itemSchema->getAttributes());

    /** @var \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResultItem $searchResultItem */
    foreach ($searchResult->getSearchResultItems() as $searchResultItem) {
      foreach (array_keys($searchResultItem->getAttributes()) as $attributeCodeForSearch) {
        if (!isset($flatSchemaAttributeCodes[$attributeCodeForSearch])) {
          $errorMessage = $attributeCodeForSearch . ' does not exist in schema for ' .
                        $itemSchema->getItemCode();
          Logger::logError($errorMessage);
          throw new InvalidSchemaMatching($errorMessage);
        }
      }
    }
  }

  /**
   * Generates flat array of attribute codes.
   *
   * @param array $attributes
   *
   * @return array
   */
  private function generateFlatItemSchemaAttributes(array $attributes) {
    $allAttributeCodes = [];

    /** @var \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute|ComplexSchemaAttribute $attribute */
    foreach ($attributes as $attribute) {
      $allAttributeCodes[$attribute->getCode()] = $attribute;

      if (!($attribute instanceof ComplexSchemaAttribute)) {
        continue;
      }

      $nestedAttributes = $attribute->getAttributes();
      if (is_array($nestedAttributes) && count($nestedAttributes) > 0) {
        $allAttributeCodes = $allAttributeCodes + $this->generateFlatItemSchemaAttributes($nestedAttributes);
      }
    }

    return $allAttributeCodes;
  }

  /**
   * @param \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute[] $schemaAttributes
   * @param Filter $generatedFilter
   * @return bool
   */
  private function isFilterMatchingTheSchema($schemaAttributes, Filter $generatedFilter) {
    return
            isset($schemaAttributes[$generatedFilter->getAttributeCode()]) &&
            $schemaAttributes[$generatedFilter->getAttributeCode()]->isSearchable() &&
            count(array_diff(
            $schemaAttributes[$generatedFilter->getAttributeCode()]->getSearchableExpressions(),
            $generatedFilter->getPossibleConditions()
            )) === 0;
  }

}
