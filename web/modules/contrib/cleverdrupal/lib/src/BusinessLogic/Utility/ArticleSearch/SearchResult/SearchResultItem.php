<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\Infrastructure\Logger\Logger;

/**
 * Class SearchResultItem.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class SearchResultItem extends SerializableJson {
  /**
   * @var string*/
  private $itemCode;

  /**
   * @var string*/
  private $id;

  /**
   * @var  string*/
  private $title;

  /**
   * @var  \DateTime*/
  private $date;

  /**
   * @var SearchResultItemAttribute[]*/
  private $attributes;

  /**
   * SearchResult constructor.
   *
   * @param string $itemCode
   * @param string $id
   * @param string $title
   * @param \DateTime $date
   * @param SearchResultItemAttribute[] $attributes
   */
  public function __construct($itemCode, $id, $title, \DateTime $date, array $attributes) {
    $this->validateSearchResult($itemCode, $id);

    $this->itemCode = $itemCode;
    $this->id = $id;
    $this->title = $title;
    $this->date = $date;
    $this->attributes = $attributes;
  }

  /**
   * @return SearchResultItemAttribute[]
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   *
   */
  public function addAttribute(SearchResultItemAttribute $attribute) {
    $this->attributes[] = $attribute;
  }

  /**
   * Prepares object for json serialization.
   *
   * @return array
   */
  public function toArray() {
    $formattedAttributes = [];

    foreach ($this->attributes as $attribute) {
      $attributeMap = $attribute->toArray();
      $attributeCodes = array_keys($attributeMap);
      $formattedAttributes[$attributeCodes[0]] = $attributeMap[$attributeCodes[0]];
    }

    return [
      'itemCode' => $this->itemCode,
      'id' => $this->id,
      'attributes' => array_merge(
          [
            'title' => $this->title,
            'date' => $this->date->format('Y-m-d\TH:i:s.u\Z'),
          ],
          $formattedAttributes
      ),
    ];
  }

  /**
   *
   */
  private function validateSearchResult($itemCode, $id) {
    if (empty($itemCode)) {
      Logger::logError('Item code for search result is mandatory.');
      throw new \InvalidArgumentException('Item code for search result is mandatory.');
    }

    if (empty($id)) {
      Logger::logError('Id for search result is mandatory.');
      throw new \InvalidArgumentException('Id for search result is mandatory.');
    }
  }

}
