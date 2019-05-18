<?php

namespace Drupal\query\Common;

/**
 * Class Expression
 *
 * @package Drupal\query\Common
 */
class Expression {
  /**
   * @var string
   *   The operator to use in the condition.
   */
  private $operator;

  /**
   * @var array|string|NULL One or more values to compare against.
   */
  private $mixed;

  /**
   * @var string
   *   Optional 2-digit language code for the condition.
   */
  private $language_code;

  /**
   * Condition constructor.
   *
   * @param $operator
   *   The operator to use in the condition.
   * @param array|string|NULL $values
   *   One or more values to compare against.
   * @param string
   *   Optional 2-digit language code for the condition.
   */
  public function __construct($operator, $values = NULL, $language_code = NULL) {
    $this->operator = $operator;
    $this->mixed = $values;
    $this->language_code = $language_code;
  }

  /**
   * @return array
   */
  public function getRaw() {
    return [
      'operator' => $this->operator,
      'values' => $this->mixed,
      'language_code' => $this->language_code,
    ];
  }

  /**
   * @return Expression
   */
  public static function isNull() {
    return new static(Operator::TYPE_NULL);
  }

  /**
   * @return Expression
   */
  public static function isEmpty() {
    return new static(Operator::TYPE_EMPTY);
  }

  /**
   * @return Expression
   */
  public static function isNotEmpty() {
    return new static(Operator::TYPE_NOT_EMPTY);
  }

  /**
   * Builds an 'equivalent' expression.
   *
   * @param string $string
   *   The string the condition will compare to.
   *
   * @return static
   */
  public static function isEquivalentTo($string) {
    return new static(Operator::TYPE_EQUIVALENT, $string);
  }

  /**
   * Builds an 'equals' expression.
   *
   * @param string $string
   *   The string the condition will compare to.
   *
   * @return static
   */
  public static function isEqualTo($string) {
    return new static(Operator::TYPE_EQUALS, $string);
  }

  /**
   * @param string $string
   *
   * @return Expression
   */
  public static function isNotEquivalentTo($string) {
    return new static(Operator::TYPE_NOT_EQUIVALENT, $string);
  }

  /**
   * @param string $string
   *
   * @return Expression
   */
  public static function isNotEqualTo($string) {
    return new static(Operator::TYPE_NOT_EQUALS, $string);
  }

  /**
   * @param array $array
   *
   * @return Expression
   */
  public static function isIn(array $array) {
    return new static(Operator::TYPE_IN, $array);
  }

  /**
   * @param array $array
   *
   * @return Expression
   */
  public static function isNotIn(array $array) {
    return new static(Operator::TYPE_NOT_IN, $array);
  }

  /**
   * Whether the collection in context has ALL the given value(s).
   *
   * @param mixed $mixed
   *
   * @return Expression
   */
  public static function hasAllOf($mixed) {
    $array = is_array($mixed) ? $mixed : [$mixed];
    return new static(Operator::TYPE_HAS, $array);
  }

  /**
   * Whether the collection in context has NONE of the given value(s).
   *
   * @param mixed $mixed
   *
   * @return Expression
   */
  public static function hasNoneOf($mixed) {
    $array = is_array($mixed) ? $mixed : [$mixed];
    return new static(Operator::TYPE_HAS_NOT, $array);
  }

  /**
   * @param string $string
   *
   * @return Expression
   */
  public static function isGreaterThan($string) {
    return new static(Operator::TYPE_GREATER_THAN, $string);
  }

  /**
   * @param string $string
   *
   * @return Expression
   */
  public static function isGreaterThanOrEqualTo($string) {
    return new static(Operator::TYPE_GREATER_THAN_EQUAL_TO, $string);
  }

  /**
   * @param string $string
   *
   * @return Expression
   */
  public static function isLessThan($string) {
    return new static(Operator::TYPE_LESS_THAN, $string);
  }

  /**
   * @param string $string
   *
   * @return Expression
   */
  public static function isLessThanOrEqualTo($string) {
    return new static(Operator::TYPE_LESS_THAN_EQUAL_TO, $string);
  }

  /**
   * @param string $a
   * @param string $b
   *
   * @return Expression
   */
  public static function isBetween($a, $b) {
    return new static(Operator::TYPE_BETWEEN, $a, $b);
  }

  /**
   * @param string $a
   * @param string $b
   * @return Expression
   */
  public static function isOutside($a, $b) {
    return new static(Operator::TYPE_OUTSIDE, $a, $b);
  }

  /**
   * @param $a
   * @param string $operator
   * @param $b
   * @param $c
   *
   * @return bool
   *   TRUE if $a relates to $b according to the given operator.
   *   TRUE if $a relates to $b & $c according to the given operator.
   */
  public static function evaluate($a, $operator, $b, $c = NULL) {
    switch ($operator) {
      case Operator::TYPE_BETWEEN:
        return $b < $a && $a < $c;

      case Operator::TYPE_OUTSIDE:
        return $a < $b && $c < $a;
    }

    if (NULL !== $c) {
      throw new \LogicException(vsprintf('Argument C not compatible with operator: %s', [
        $operator,
      ]));
    }

    switch ($operator) {
      case Operator::TYPE_EQUALS:
        return $a == $b;

      case Operator::TYPE_NOT_EQUALS:
        return $a != $b;

      case Operator::TYPE_GREATER_THAN:
        return $a > $b;

      case Operator::TYPE_LESS_THAN:
        return $a < $b;

      case Operator::TYPE_GREATER_THAN_EQUAL_TO:
        return $a >= $b;

      case Operator::TYPE_LESS_THAN_EQUAL_TO:
        return $a <= $b;

      default:
        throw new \DomainException(sprintf('Unknown operator: %s', $operator));
    }
  }

  /**
   * @return string
   *   The operator for the condition.
   */
  public function getOperator() {
    return $this->operator;
  }

  /**
   * Gets one or more values to compare against.
   *
   * @return array|string|NULL
   *   One or more values to compare against.
   */
  public function getValues() {
    return $this->mixed;
  }

  /**
   * Gets one value from the defined values.
   *
   * @param int $offset
   *   Optional. Which value to get.
   *
   * @return array|string|NULL
   *   The matching value.
   */
  public function getValue($offset = 0) {
    return is_array($this->mixed) ? $this->mixed[$offset] : $this->mixed;
  }

  /**
   * @return string|NULL
   *   The 2-digit language code for the condition, if any.
   */
  public function getLanguageCode() {
    return $this->language_code;
  }
}
