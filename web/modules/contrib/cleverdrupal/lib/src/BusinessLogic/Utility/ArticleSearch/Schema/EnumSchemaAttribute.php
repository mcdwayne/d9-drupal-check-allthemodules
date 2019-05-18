<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class EnumSchemaAttribute, enum type of attribute for schema .
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
class EnumSchemaAttribute extends SchemaAttribute {
  /**
   * @var array*/
  private $possibleValues;

  /**
   * EnumSchemaAttribute constructor.
   *
   * @param string $code
   * @param string $name
   * @param bool $searchable
   * @param array $searchableExpressions,
   *   Conditions enum contains all possible values for searchable expressions.
   * @param Enum[] $possibleValues
   */
  public function __construct($code, $name, $searchable, array $searchableExpressions, array $possibleValues) {
    parent::__construct($code, $name, $searchable, $searchableExpressions);

    $this->type = SchemaAttributeTypes::ENUM;
    $this->possibleValues = $possibleValues;
  }

  /**
   * Prepares object for json serialization.
   *
   * @return array
   */
  public function toArray() {
    $result = parent::toArray();
    /** @var Enum $value */
    foreach ($this->possibleValues as $value) {
      $result['possibleValues'][] = ['label' => $value->getLabel(), 'value' => $value->getValue()];
    }

    return $result;
  }

}
