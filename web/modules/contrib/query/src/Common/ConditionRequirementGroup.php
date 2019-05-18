<?php

namespace Drupal\query\Common;

/**
 * Class ConditionRequirementGroup
 *
 * @package Drupal\query\Common
 */
class ConditionRequirementGroup {
  /**
   * @var string
   */
  private $conjunction;

  /**
   * @var array
   */
  private $requirements = [];

  /**
   * ConditionRequirementGroup constructor.
   *
   * @param string $conjunction
   */
  public function __construct($conjunction) {
    $this->conjunction = $conjunction;
  }

  /**
   * @param string $conjunction
   *
   * @return static
   */
  public static function create($conjunction = Conjunction::TYPE_AND) {
    return new static($conjunction);
  }

  /**
   * @return string
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * @return Expression[]
   */
  public function getRequirements() {
    return $this->requirements;
  }

  /**
   * @param Expression $expression
   */
  public function append(Expression $expression) {
    $this->requirements[] = $expression;
  }
}
