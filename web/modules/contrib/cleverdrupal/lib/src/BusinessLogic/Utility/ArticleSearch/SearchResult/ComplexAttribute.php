<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class ComplexAttribute, base class for all complex types of attributes.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
abstract class ComplexAttribute extends SearchResultItemAttribute {
  /**
   * @var  SearchResultItemAttribute[]*/
  protected $attributes;

  /**
   * ComplexCollectionAttribute constructor.
   *
   * @param string $code
   * @param SearchResultItemAttribute[] $attributes
   */
  public function __construct($code, array $attributes = []) {
    parent::__construct($code);
    $this->attributes = $attributes;
  }

  /**
   * Adds new attribute to the list of attributes.
   *
   * @param SearchResultItemAttribute $attribute
   */
  public function addAttribute(SearchResultItemAttribute $attribute) {
    $this->attributes[] = $attribute;
  }

}
