<?php

namespace CleverReach\BusinessLogic\Utility\ArticleSearch;

/**
 * Class SerializableJson, base class for all objects that use utility json serialization methods.
 *
 * @package CleverReach\BusinessLogic\Utility\ArticleSearch
 */
abstract class SerializableJson {

  /**
   * Prepares object for json serialization.
   *
   * @throws \Exception when transformation to array can't be done.
   *
   * @return array
   */
  abstract public function toArray();

  /**
   * Returns JSON representation of the object.
   *
   * @return string
   */
  public function toJson() {
    return json_encode($this->toArray());
  }

}
