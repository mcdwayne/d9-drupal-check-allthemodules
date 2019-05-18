<?php

namespace Drupal\business_rules;

/**
 * Class VariablesSet to be returned on each BusinessRulesVariable plugin.
 *
 * @package Drupal\business_rules
 */
class VariablesSet {

  /**
   * The variables storage.
   *
   * @var array
   */
  protected $variables = [];

  /**
   * Append the variable to the array.
   *
   * @param \Drupal\business_rules\VariableObject $variable
   *   The variable set.
   */
  public function append(VariableObject $variable) {
    $this->variables[$variable->getId()] = $variable;
  }

  /**
   * Replace the content of one variable.
   *
   * @param string $variable_id
   *   The variable id.
   * @param mixed $value
   *   The variable value.
   *
   * @internal param \Drupal\business_rules\VariableObject $variable
   */
  public function replaceValue($variable_id, $value) {
    if ($this->count()) {

      foreach ($this->variables as $key => $var) {
        if ($var->getId() == $variable_id) {
          $this->variables[$key]->setValue($value);
        }
      }
    }
  }

  /**
   * Number of variables inside the set.
   *
   * @return int
   *   The number of variables on the variable set.
   */
  public function count() {
    return count($this->variables);
  }

  /**
   * Remove one variable from the variables set.
   *
   * @param string $variable_id
   *   The variable id.
   */
  public function remove($variable_id) {
    if ($this->count()) {

      foreach ($this->variables as $key => $variable) {
        if ($variable->getId() == $variable_id) {
          unset($this->variables[$key]);
        }
      }
    }
  }

  /**
   * Return all variables.
   *
   * @return array
   *   Array of variables.
   */
  public function getVariables() {
    return $this->variables;
  }

  /**
   * Return variable by id.
   *
   * @param string $variable_id
   *   The variable id.
   *
   * @return VariableObject|null
   *   The variable object or null if variable id not exists.
   */
  public function getVariable($variable_id) {
    if ($this->count()) {

      foreach ($this->variables as $variable) {
        if ($variable->getId() == $variable_id) {
          return $variable;
        }
      }
    }

    return NULL;
  }

  /**
   * Return the variables ids.
   *
   * @return array
   *   Array of variables ids.
   */
  public function getVariablesIds() {
    $ids = [];

    if ($this->count()) {
      foreach ($this->variables as $variable) {
        $ids[] = $variable->getId();
      }
    }

    return $ids;
  }

}
