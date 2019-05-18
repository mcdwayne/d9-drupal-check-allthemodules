<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class SimpleAttribute, base class for all simple types of attributes in search result .
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
abstract class SimpleAttribute extends SearchResultItemAttribute {
  /**
   * @var  string*/
  protected $value;

  /**
   * @return string
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * SimpleAttribute constructor.
   *
   * @param string $code
   * @param string $value
   */
  public function __construct($code, $value) {
    parent::__construct($code);

    $this->value = $value;
  }

  /**
   *
   */
  public function toArray() {
    return [$this->code => $this->value];
  }

}
