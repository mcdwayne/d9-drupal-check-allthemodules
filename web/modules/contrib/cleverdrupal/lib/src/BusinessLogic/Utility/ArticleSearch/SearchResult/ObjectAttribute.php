<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class ObjectAttribute, object type of attribute for search result.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class ObjectAttribute extends ComplexAttribute {

  /**
   *
   */
  public function toArray() {
    $result = [$this->code => []];

    foreach ($this->attributes as $attribute) {
      $attributeMap = $attribute->toArray();
      $attributeCodes = array_keys($attributeMap);
      $result[$this->code][$attributeCodes[0]] = $attributeMap[$attributeCodes[0]];
    }

    return $result;
  }

}
