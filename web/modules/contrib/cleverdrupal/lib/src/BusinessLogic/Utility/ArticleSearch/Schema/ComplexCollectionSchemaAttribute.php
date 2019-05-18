<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class ComplexCollectionSchemaAttribute, used for creating complex collection attribute for schema.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
class ComplexCollectionSchemaAttribute extends ComplexSchemaAttribute {

  /**
   * ComplexCollectionSchemaAttribute constructor.
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

    $this->type = SchemaAttributeTypes::COLLECTION;
  }

}
