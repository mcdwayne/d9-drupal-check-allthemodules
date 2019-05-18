<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch;

/**
 * Class FilterParser.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch
 */
class FilterParser {

  /**
   * Generates array of filter objects based on passed parameters.
   *
   * @param string $itemCode,
   *   code of searchable item.
   * @param string|null $itemId,
   *   id of searchable item.
   * @param string|null $query,
   *   url encoded URL.
   *
   * @throws \InvalidArgumentException if filter is not created with valid parameters
   *
   * @return Filter[]
   */
  public function generateFilters($itemCode, $itemId = NULL, $query = NULL) {
    $generatedFilters = [
      new Filter('itemCode', $itemCode, Conditions::EQUALS, Operators::AND_OPERATOR),
    ];

    if (!empty($itemId)) {
      $generatedFilters[] = new Filter('itemId', $itemId, Conditions::EQUALS, Operators::AND_OPERATOR);
    }

    if (!empty($query)) {
      $generatedFilters = array_merge($generatedFilters, $this->createFiltersFromQuery($query));
    }

    return $generatedFilters;
  }

  /**
   * Parses query based on known rules. Only AND operator is supported. Each expression is in format
   * "{$attributeName} {$condition} {$attributeValue}".
   *
   * @param string $query
   *   in format "title ct 'great' and date gt '2012-04-23T18:25:43.511Z'".
   *
   * @throws \InvalidArgumentException if filter is not created with valid parameters
   *
   * @return Filter[]
   */
  private function createFiltersFromQuery($query) {
    $query = urldecode($query);
    $operatorPartInQuery = ' ' . Operators::AND_OPERATOR . ' ';
    $query = str_replace($operatorPartInQuery, strtolower($operatorPartInQuery), $query);
    $queryExpressions = explode(strtolower($operatorPartInQuery), $query);
    $generatedFilters = [];

    foreach ($queryExpressions as $queryExpression) {
      $expressionCondition = $this->findConditionInQueryExpression($queryExpression);
      $conditionPartInQuery = ' ' . $expressionCondition . ' ';

      $filterParts = !empty($expressionCondition) ? explode($conditionPartInQuery, $queryExpression) : [];

      $attributeCode = isset($filterParts[0]) ? $filterParts[0] : NULL;

      if (empty($expressionCondition)) {
        // If condition is invalid just add whole string as value.
        $attributeValue = $attributeCode;
      }
      else {
        $attributeValue = isset($filterParts[1]) ? str_replace("'", '', $filterParts[1]) : NULL;
      }

      $generatedFilters[] = new Filter(
        trim($attributeCode),
        trim($attributeValue),
        $expressionCondition,
        Operators::AND_OPERATOR
      );
    }

    return $generatedFilters;
  }

  /**
   * Finds condition in expression in query.
   *
   * @param $queryExpression
   *
   * @return string
   */
  private function findConditionInQueryExpression($queryExpression) {
    $expressionCondition = '';

    foreach (Filter::getPossibleConditions() as $condition) {
      $conditionPartInQuery = ' ' . $condition . ' ';
      if (strpos($queryExpression, strtolower($conditionPartInQuery)) > -1) {
        $expressionCondition = $condition;
        break;
      }
    }

    return $expressionCondition;
  }

}
