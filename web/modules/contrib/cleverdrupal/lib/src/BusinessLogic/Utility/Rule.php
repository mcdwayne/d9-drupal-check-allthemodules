<?php

namespace CleverReach\BusinessLogic\Utility;

/**
 *
 */
class Rule {
  /**
   * @var string
   */
  private $field;
  /**
   * @var string
   */
  private $logic;
  /**
   * @var string
   */
  private $condition;

  /**
   * Rule constructor.
   *
   * @param string $field
   * @param string $logic
   * @param string $condition
   */
  public function __construct($field, $logic, $condition) {
    $this->field = $field;
    $this->logic = $logic;
    $this->condition = $condition;
  }

  /**
   * @return mixed
   */
  public function getField() {
    return $this->field;
  }

  /**
   * @param mixed $field
   */
  public function setField($field) {
    $this->field = $field;
  }

  /**
   * @return mixed
   */
  public function getLogic() {
    return $this->logic;
  }

  /**
   * @param mixed $logic
   */
  public function setLogic($logic) {
    $this->logic = $logic;
  }

  /**
   * @return mixed
   */
  public function getCondition() {
    return $this->condition;
  }

  /**
   * @param mixed $condition
   */
  public function setCondition($condition) {
    $this->condition = $condition;
  }

  /**
   *
   */
  public function toArray() {
    return [
      'field' => $this->field,
      'logic' => $this->logic,
      'condition' => $this->condition,
    ];
  }

}
