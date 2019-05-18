<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class ComplexCollectionAttribute, used for creating Complex collection attribute.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class ComplexCollectionAttribute extends ComplexAttribute {

  /**
   *
   */
  public function toArray() {
    $result = [$this->code => []];

    foreach ($this->attributes as $attribute) {
      $attributeMap = $attribute->toArray();
      $attributeCodes = array_keys($attributeMap);
      if ($attribute instanceof ObjectAttribute) {
        $result[$this->code][] = $attributeMap[$attributeCodes[0]];
      }
      else {
        $result[$this->code][][$attributeCodes[0]] = $attributeMap[$attributeCodes[0]];
      }
    }

    return $result;
  }

}
