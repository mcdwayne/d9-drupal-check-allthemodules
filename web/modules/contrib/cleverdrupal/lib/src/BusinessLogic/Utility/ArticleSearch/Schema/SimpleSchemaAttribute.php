<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\Infrastructure\Logger\Logger;

/**
 * Class SimpleSchemaAttribute, simple type of attribute in schema.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
class SimpleSchemaAttribute extends SchemaAttribute {
  /**
   * @var arrayAllpossibleattributetypes*/
  private $attributeTypes = [
    SchemaAttributeTypes::AUTHOR,
    SchemaAttributeTypes::URL,
    SchemaAttributeTypes::TEXT,
    SchemaAttributeTypes::IMAGE,
    SchemaAttributeTypes::DATE,
    SchemaAttributeTypes::HTML,
  ];

  /**
   *
   */
  public function __construct($code, $name, $searchable, array $searchableExpressions, $type) {
    $this->validate($type);

    parent::__construct($code, $name, $searchable, $searchableExpressions);

    $this->type = $type;
  }

  /**
   *
   */
  private function validate($type) {
    if (!in_array($type, $this->attributeTypes)) {
      $errorMessage = 'Invalid type for schema attribute: ' . $type . '. ' .
                'Type must value from enum: ' . implode(',', $this->attributeTypes);
      Logger::logError($errorMessage);
      throw new \InvalidArgumentException($errorMessage);
    }
  }

}
