<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class ObjectSchemaAttribute, object type of attribute for schema.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
class ObjectSchemaAttribute extends ComplexSchemaAttribute {

  /**
   * ObjectSchemaAttribute constructor.
   *
   * @param string $code
   * @param string $name
   * @param bool $searchable
   * @param array $searchableExpressions,
   *   Conditions enum contains all possible values for searchable expressions.
   * @param SchemaAttribute[] $attributes
   */
  public function __construct($code, $name, $searchable, array $searchableExpressions, array $attributes) {
    parent::__construct($code, $name, $searchable, $searchableExpressions, $attributes);

    $this->type = SchemaAttributeTypes::OBJECT;
  }

}
