<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult;

use CleverReach\Infrastructure\Logger\Logger;

/**
 * Class NumberAttribute, number attribute for search result.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult
 */
class NumberAttribute extends SimpleAttribute {

  /**
   *
   */
  public function __construct($code, $value) {
    parent::__construct($code, $value);

    if (!is_numeric($value)) {
      Logger::logError('Passed value: ' . $this->value . ' can not be cast to numeric type.');
      throw new \InvalidArgumentException('Passed value: ' . $this->value . ' can not be cast to numeric type.');
    }

    if (strpos($value, '.') > 0) {
      $this->value = floatval($value);
    }
    else {
      $this->value = intval($value);
    }
  }

  /**
   *
   */
  public function toArray() {
    return [$this->code => $this->value];
  }

}
