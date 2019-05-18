<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\Infrastructure\Logger\Logger;

/**
 * Class ComplexSchemaAttribute, base class for all complex attributes that will be used in schema.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
abstract class ComplexSchemaAttribute extends SchemaAttribute {
  /**
   * @var SchemaAttribute[]*/
  private $attributes;

  /**
   * ComplexSchemaAttribute constructor.
   *
   * @param string $code
   * @param string $name
   * @param bool $searchable
   * @param array $searchableExpressions,
   *   Conditions enum contains all possible values for searchable expressions.
   * @param SchemaAttribute[] $attributes
   */
  public function __construct(
        $code,
        $name,
        $searchable,
        array $searchableExpressions = [],
        array $attributes = []
    ) {
    $this->validate($attributes);
    parent::__construct($code, $name, $searchable, $searchableExpressions);

    $this->attributes = $attributes;
  }

  /**
   * @return SchemaAttribute[]
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Adds attribute to a schema.
   *
   * @param SchemaAttribute $attribute
   */
  public function addSchemaAttribute(SchemaAttribute $attribute) {
    $this->attributes[] = $attribute;
  }

  /**
   * Prepares object for json serialization.
   *
   * @return array
   */
  public function toArray() {
    $result = parent::toArray();

    foreach ($this->attributes as $attribute) {
      $result['attributes'][] = $attribute->toArray();
    }

    return $result;
  }

  /**
   * Validates if attributes array has valid elements.
   *
   * @param SchemaAttribute[] $attributes
   */
  private function validate($attributes) {
    foreach ($attributes as $attribute) {
      if (!($attribute instanceof SchemaAttribute)) {
        Logger::logError('Invalid attribute type passed to complex schema attribute.');
        throw new \InvalidArgumentException('Invalid attribute type passed to complex schema attribute.');
      }
    }
  }

}
