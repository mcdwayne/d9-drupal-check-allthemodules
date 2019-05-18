<?php

namespace CleverReach\BusinessLogic\Utility;

/**
 *
 */
class Filter {
  const CLASS_NAME = __CLASS__;

  /**
   * @var  int*/
  private $id;

  /**
   * @var  string*/
  private $name;

  /**
   * @var  string*/
  private $operator;

  /**
   * @var Rule[]*/
  private $allRules = [];

  /**
   * Filter constructor.
   *
   * @param $name
   * @param $rule
   * @param $operator
   */
  public function __construct($name, Rule $rule, $operator = NULL) {
    $this->name = $name;

    $this->operator = $operator ?: 'AND';

    array_push($this->allRules, $rule);
  }

  /**
   *
   */
  public function toArray() {
    return [
      'name' => $this->name,
      'operator' => $this->operator,
      'rules' => $this->rulesToArray(),
    ];
  }

  /**
   * Get allRules.
   *
   * @return Rule[]
   */
  public function getAllRules() {
    return $this->allRules;
  }

  /**
   * @param Rule[] $allRules
   */
  public function setAllRules($allRules) {
    $this->allRules = $allRules;
  }

  /**
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param int $id
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   *
   */
  public function getFirstCondition() {
    return $this->allRules[0]->getCondition();
  }

  /**
   * @param Rule $rule
   */
  public function addRule(Rule $rule) {
    array_push($this->allRules, $rule);
  }

  /**
   * Converts allRules[Rule] to allRules[array[]].
   *
   * @return array[array]
   */
  private function rulesToArray() {
    $ret = [];
    foreach ($this->allRules as $rule) {
      array_push($ret, $rule->toArray());
    }

    return $ret;
  }

}
