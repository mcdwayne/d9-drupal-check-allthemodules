<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class Enum, enumeration object usd for creating EnumSchemaAttribute instance.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
class Enum {
  /**
   * @var  string*/
  private $label;

  /**
   * @var  mixed*/
  private $value;

  /**
   *
   */
  public function __construct($label, $value) {
    $this->label = $label;
    $this->value = $value;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @return mixed
   */
  public function getValue() {
    return $this->value;
  }

}
