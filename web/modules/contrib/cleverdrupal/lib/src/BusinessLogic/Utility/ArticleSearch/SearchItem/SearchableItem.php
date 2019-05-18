<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem;

use CleverReach\BusinessLogic\Utility\ArticleSearch\SerializableJson;
use CleverReach\Infrastructure\Logger\Logger;

/**
 *
 */
class SearchableItem extends SerializableJson {
  /**
   * @var  string*/
  private $code;

  /**
   * @var  string*/
  private $name;

  /**
   * SearchableItem constructor.
   *
   * @param string $code
   * @param string $name
   */
  public function __construct($code, $name) {
    $this->validateSearchableItem($code, $name);

    $this->code = $code;
    $this->name = $name;
  }

  /**
   * Prepares object for json serialization.
   *
   * @return array
   */
  public function toArray() {
    return ['code' => $this->code, 'name' => $this->name];
  }

  /**
   *
   */
  private function validateSearchableItem($code, $name) {
    if (empty($code)) {
      Logger::logError('Code for searchable item is mandatory.');
      throw new \InvalidArgumentException('Code for searchable item is mandatory.');
    }

    if (empty($name)) {
      Logger::logError('Name for searchable item is mandatory.');
      throw new \InvalidArgumentException('Name for searchable item is mandatory.');
    }
  }

}
