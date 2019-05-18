<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\BusinessLogic\Utility\ArticleSearch\SerializableJson;

/**
 * Class SearchResultItemAttribute, base class for all attributes in search result.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
abstract class SearchResultItemAttribute extends SerializableJson {
  /**
   * @var  string*/
  protected $code;

  /**
   * SearchResultItemAttribute constructor.
   *
   * @param string $code
   */
  public function __construct($code) {
    $this->code = $code;
  }

}
