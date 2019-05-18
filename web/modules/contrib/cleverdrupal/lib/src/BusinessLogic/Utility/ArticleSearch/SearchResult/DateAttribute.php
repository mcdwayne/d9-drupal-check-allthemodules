<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

/**
 * Class DateAttribute, Date attribute in search result.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class DateAttribute extends SearchResultItemAttribute {
  /**
   * @var  \DateTime*/
  private $value;

  /**
   * DateAttribute constructor.
   *
   * @param string $code
   * @param \DateTime $value
   */
  public function __construct($code, \DateTime $value) {
    parent::__construct($code);
    $this->value = $value;
  }

  /**
   *
   */
  public function toArray() {
    return [$this->code => $this->value->format('Y-m-d\TH:i:s.u\Z')];
  }

}
