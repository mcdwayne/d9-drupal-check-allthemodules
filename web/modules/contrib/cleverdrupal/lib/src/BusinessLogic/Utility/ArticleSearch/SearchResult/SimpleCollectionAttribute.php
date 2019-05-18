<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class SimpleCollectionAttribute, simple collection type of attribute for search result.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class SimpleCollectionAttribute extends SearchResultItemAttribute {
  /**
   * @var  array*/
  private $attributes;

  /**
   * ComplexCollectionAttribute constructor.
   *
   * @param string $code
   * @param SimpleAttribute[] $attributes
   */
  public function __construct($code, array $attributes = []) {
    parent::__construct($code);
    $this->attributes = $attributes;
  }

  /**
   * Adds new attribute to the list of attributes.
   *
   * @param SimpleAttribute $attribute
   */
  public function addAttribute(SimpleAttribute $attribute) {
    $this->attributes[] = $attribute;
  }

  /**
   *
   */
  public function toArray() {
    $formattedAttributes = [];

    foreach ($this->attributes as $attribute) {
      $attributeMap = $attribute->toArray();
      $attributeMapValues = array_values($attributeMap);
      $formattedAttributes[] = reset($attributeMapValues);
    }

    return [$this->code => $formattedAttributes];
  }

}
