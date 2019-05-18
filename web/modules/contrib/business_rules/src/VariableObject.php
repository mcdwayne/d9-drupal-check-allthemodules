<?php

namespace Drupal\business_rules;

/**
 * Class Variable to be used on BusinessRulesVariable plugins.
 *
 * @package Drupal\business_rules
 */
class VariableObject {

  /**
   * The variable ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The variable value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The variable type.
   *
   * @var string
   */
  protected $type;

  /**
   * VariableObject constructor.
   *
   * @param string $id
   *   The variable id.
   * @param mixed $value
   *   The variable value.
   * @param string $type
   *   The variable type.
   */
  public function __construct($id = NULL, $value = NULL, $type = NULL) {
    $this->setId($id);
    $this->setValue($value);
    $this->setType($type);
  }

  /**
   * Get the variable id.
   *
   * @return string
   *   The variable id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set the variable id.
   *
   * @param string $id
   *   The variable id.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Get the variable value.
   *
   * @return mixed
   *   The variable value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Set the variable value.
   *
   * @param mixed $value
   *   The variable value.
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Get the variable type, usually the plugin id.
   *
   * @return string
   *   The variable type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set the variable type, usually the plugin id..
   *
   * @param string $type
   *   The variable type.
   */
  public function setType($type) {
    $this->type = $type;
  }

}
