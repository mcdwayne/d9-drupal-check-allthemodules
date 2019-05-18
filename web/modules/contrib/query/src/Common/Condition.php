<?php

namespace Drupal\query\Common;

/**
 * Class Condition
 *
 * @package Drupal\query\Common
 */
class Condition {
  /**
   * @var string
   */
  private $key;

  /**
   * The conjunction to use for the whole group of requirement groups.
   *
   * @var string
   */
  private $conjunction = Conjunction::TYPE_AND;

  /**
   * @var ConditionRequirementGroup[]
   */
  private $requirementGroups;

  /**
   * Condition constructor.
   *
   * @param string|null $key
   */
  public function __construct($key = NULL) {
    $this->key = $key;
  }

  /**
   * @param string|null $key
   *
   * @return static
   */
  public static function create($key = NULL) {
    return new static($key);
  }

  /**
   * @return string
   */
  public function getGroupConjunction() {
    return $this->conjunction;
  }

  /**
   * @return ConditionRequirementGroup[]
   */
  public function getRequirementGroups() {
    return $this->requirementGroups;
  }

  /**
   * @param string $conjunction
   *
   * @return ConditionRequirementGroup
   */
  private function getRequirementGroup($conjunction = Conjunction::TYPE_AND) {
    $current = current($this->requirementGroups);
    if (NULL === $current) {
      $this->requirementGroups[] = ConditionRequirementGroup::create($conjunction);
    }
    return current($this->requirementGroups);
  }

  /**
   * @return string
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * @param string $key
   *
   * @return static
   */
  public function setKey($key) {
    $this->key = $key;
    return $this;
  }

  /**
   * @param string $key
   *
   * @return static
   */
  public function key($key) {
    return $this->setKey($key);
  }

  /**
   * @return static
   */
  public function isNull() {
    $this->getRequirementGroup()->append(Expression::isNull());
    return $this;
  }

  /**
   * @return static
   */
  public function isEmpty() {
    $this->getRequirementGroup()->append(Expression::isEmpty());
    return $this;
  }

  /**
   * @return static
   */
  public function isNotEmpty() {
    $this->getRequirementGroup()->append(Expression::isNotEmpty());
    return $this;
  }

  /**
   * @param bool $strict
   *
   * @return static
   */
  public function is($strict = FALSE) {
    return $strict ? $this->isEqualTo(TRUE) : $this->isEquivalentTo(TRUE);
  }

  /**
   * @param string $value
   *
   * @return static
   */
  public function isEquivalentTo($value) {
    $this->getRequirementGroup()->append(Expression::isEquivalentTo($value));
    return $this;
  }

  /**
   * @param string $value
   *
   * @return static
   */
  public function isEqualTo($value) {
    $this->getRequirementGroup()->append(Expression::isEqualTo($value));
    return $this;
  }

  /**
   * @param string $value
   *
   * @return static
   */
  public function isNotEquivalentTo($value) {
    $this->getRequirementGroup()->append(Expression::isNotEquivalentTo($value));
    return $this;
  }

  /**
   * @param string $value
   *
   * @return static
   */
  public function isNotEqualTo($value) {
    $this->getRequirementGroup()->append(Expression::isNotEqualTo($value));
    return $this;
  }

  /**
   * @param array $value
   *
   * @return static
   */
  public function isIn(array $value) {
    $this->getRequirementGroup()->append(Expression::isIn($value));
    return $this;
  }

  /**
   * @param mixed $mixed
   *
   * @return static
   */
  public function hasAllOf($mixed) {
    $array = is_array($mixed) ? $mixed : [$mixed];
    $this->getRequirementGroup()->append(Expression::hasAllOf($array));
    return $this;
  }

  /**
   * @param mixed $mixed
   *
   * @return static
   */
  public function hasNoneOf($mixed) {
    $array = is_array($mixed) ? $mixed : [$mixed];
    $this->getRequirementGroup()->append(Expression::hasNoneOf($array));
    return $this;
  }

  /**
   * @param array $value
   *
   * @return static
   */
  public function isNotIn(array $value) {
    $this->getRequirementGroup()->append(Expression::isNotIn($value));
    return $this;
  }

  /**
   * @param string $string
   *
   * @return static
   */
  public function isGreaterThan($string) {
    $this->getRequirementGroup()->append(Expression::isGreaterThan($string));
    return $this;
  }

  /**
   * @param string $string
   *
   * @return static
   */
  public function isGreaterThanOrEqualTo($string) {
    $this->getRequirementGroup()->append(Expression::isGreaterThanOrEqualTo($string));
    return $this;
  }

  /**
   * @param string $string
   *
   * @return static
   */
  public function isLessThan($string) {
    $this->getRequirementGroup()->append(Expression::isLessThan($string));
    return $this;
  }

  /**
   * @param string $string
   *
   * @return static
   */
  public function isLessThanOrEqualTo($string) {
    $this->getRequirementGroup()->append(Expression::isLessThanOrEqualTo($string));
    return $this;
  }

  /**
   * @param string $a
   * @param string $b
   *
   * @return static
   */
  public function isBetween($a, $b) {
    $this->getRequirementGroup()->append(Expression::isBetween($a, $b));
    return $this;
  }

  /**
   * @param string $a
   * @param string $b
   * @return static
   */
  public function isOutside($a, $b) {
    $this->getRequirementGroup()->append(Expression::isOutside($a, $b));
    return $this;
  }
}
