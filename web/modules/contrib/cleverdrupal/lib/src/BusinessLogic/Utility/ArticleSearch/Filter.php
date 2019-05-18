<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch;

use CleverReach\Infrastructure\Logger\Logger;

/**
 *
 */
class Filter {
  /**
   * @var  string*/
  private $attributeCode;

  /**
   * @var  string*/
  private $attributeValue;

  /**
   * @var  string*/
  private $condition;

  /**
   * @var  string*/
  private $operator;

  /**
   * All possible conditions.
   *
   * @var array
   */
  private static $possibleConditions = [
    Conditions::CONTAINS,
    Conditions::EQUALS,
    Conditions::GREATER_EQUAL,
    Conditions::GREATER_THAN,
    Conditions::LESS_EQUAL,
    Conditions::LESS_THAN,
    Conditions::NOT_EQUAL,
  ];

  /**
   * All possible operators.
   *
   * @var array
   */
  private static $possibleOperators = [
    Operators::AND_OPERATOR,
  ];

  /**
   * @return string
   */
  public function getAttributeCode() {
    return $this->attributeCode;
  }

  /**
   * @return string
   */
  public function getAttributeValue() {
    return $this->attributeValue;
  }

  /**
   * @return string
   */
  public function getCondition() {
    return $this->condition;
  }

  /**
   * @return string
   */
  public function getOperator() {
    return $this->operator;
  }

  /**
   * @return array
   */
  public static function getPossibleConditions() {
    return self::$possibleConditions;
  }

  /**
   * Filter constructor.
   *
   * @param string $attributeCode
   * @param string $attributeValue
   * @param string $condition
   * @param string $operator
   */
  public function __construct($attributeCode, $attributeValue, $condition, $operator) {
    $this->validateFilter($attributeCode, $attributeValue, $condition, $operator);

    $this->attributeCode = $attributeCode;
    $this->attributeValue = $attributeValue;
    $this->condition = $condition;
    $this->operator = $operator;
  }

  /**
   *
   */
  private function validateFilter($attributeCode, $attributeValue, $condition, $operator) {
    if ($attributeCode === NULL) {
      Logger::logError('Attribute code for filter is mandatory.');
      throw new \InvalidArgumentException('Attribute code for filter is mandatory.');
    }

    if ($attributeValue === NULL) {
      Logger::logError('Attribute value for filter is mandatory.');
      throw new \InvalidArgumentException('Attribute value for filter is mandatory.');
    }

    if (!in_array($condition, self::$possibleConditions)) {
      $errorMessage = 'Condition for filter must be in the set of values: ' .
                json_encode(self::$possibleConditions);
      Logger::logError($errorMessage);
      throw new \InvalidArgumentException($errorMessage);
    }

    if (!in_array($operator, self::$possibleOperators)) {
      $errorMessage = 'Operator for filter must be in the set of values: ' . json_encode(self::$possibleOperators);
      Logger::logError($errorMessage);
      throw new \InvalidArgumentException($errorMessage);
    }
  }

}
