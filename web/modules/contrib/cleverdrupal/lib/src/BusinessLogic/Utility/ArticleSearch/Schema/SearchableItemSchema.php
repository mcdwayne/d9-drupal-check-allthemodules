<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\Schema;

use CleverReach\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\Infrastructure\Logger\Logger;

/**
 * Class SearchableItemSchema.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\Schema
 */
class SearchableItemSchema extends SerializableJson {
  /**
   * @var  string*/
  private $itemCode;

  /**
   * @var  SchemaAttribute[]*/
  private $attributes;

  /**
   * SearchableItemSchema constructor.
   *
   * @param string $itemCode
   * @param SchemaAttribute[] $attributes
   */
  public function __construct($itemCode, array $attributes) {
    $this->validateSearchableItemSchema($itemCode, $attributes);

    $this->itemCode = $itemCode;
    $this->attributes = $attributes;
  }

  /**
   * @return string
   */
  public function getItemCode() {
    return $this->itemCode;
  }

  /**
   * @return SchemaAttribute[]
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Adds an attribute to the list of attributes.
   *
   * @param SchemaAttribute $attribute
   */
  public function addAttribute(SchemaAttribute $attribute) {
    $this->attributes[] = $attribute;
  }

  /**
   * Prepares object for json serialization.
   *
   * @return array
   */
  public function toArray() {
    $attributes = [];

    /** @var SchemaAttribute $attribute */
    foreach ($this->attributes as $attribute) {
      $attributes[] = $attribute->toArray();
    }

    return ['itemCode' => $this->itemCode, 'attributes' => $attributes];
  }

  /**
   *
   */
  private function validateSearchableItemSchema($itemCode, $attributes) {
    if (empty($itemCode)) {
      Logger::logError('Item code for item schema is mandatory.');
      throw new \InvalidArgumentException('Item code for item schema is mandatory.');
    }

    foreach ($attributes as $attribute) {
      if (!($attribute instanceof SchemaAttribute)) {
        Logger::logError('All attributes must be instances of SchemaAttribute class.');
        throw new \InvalidArgumentException('All attributes must be instances of SchemaAttribute class.');
      }
    }
  }

}
