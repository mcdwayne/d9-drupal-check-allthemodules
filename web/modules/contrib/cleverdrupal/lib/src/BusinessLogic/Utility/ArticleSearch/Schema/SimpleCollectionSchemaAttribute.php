<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

/**
 * Class SimpleCollectionSchemaAttribute, simple collection attribute in schema.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
class SimpleCollectionSchemaAttribute extends SchemaAttribute {
  /**
   * @var  string*/
  private $attributes;

  /**
   * SimpleSchemaAttribute constructor.
   *
   * @param string $code
   * @param string $name
   * @param bool $searchable
   * @param array $searchableExpressions
   * @param string $attributes
   */
  public function __construct($code, $name, $searchable, array $searchableExpressions, $attributes) {
    parent::__construct($code, $name, $searchable, $searchableExpressions);

    $this->type = SchemaAttributeTypes::COLLECTION;

    $this->attributes = $attributes;
  }

  /**
   * Prepares object for json serialization.
   *
   * @return array
   */
  public function toArray() {
    $result = parent::toArray();
    $result['attributes'] = $this->attributes;

    return $result;
  }

}
