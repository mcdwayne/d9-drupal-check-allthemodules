<?php

namespace Drupal\query\Common;

/**
 * Class ConditionRequirements
 *
 * @package Drupal\query\Common
 */
class ConditionRequirements {
  /**
   * @var string
   */
  private $conjunction = Conjunction::TYPE_AND;

  /**
   * @var Expression[]
   */
  private $requirements = [];

  /**
   * @return string
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * @param string $conjunction
   *
   * @return static
   */
  public function setConjunction($conjunction) {
    $this->conjunction = $conjunction;
    return $this;
  }

  /**
   * @param Expression $expression
   */
  public function append(Expression $expression) {
    if (!empty($this->requirements)) {
      $this->requirements[] = $this->conjunction;
    }
    $this->requirements[] = $expression;
  }
}
