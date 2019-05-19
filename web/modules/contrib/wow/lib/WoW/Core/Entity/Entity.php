<?php

/**
 * @file
 * Definition of Entity.
 */

namespace WoW\Core\Entity;

/**
 * Defines an entity class.
 */
abstract class Entity extends \Entity {

  /**
   * Time stamp for entity's last fetch.
   *
   * @var integer
   */
  public $lastFetched = 0;

  /**
   * Timestamp for entity's last update.
   *
   * @var integer
   */
  public $lastModified = 0;

  /**
   * The entity region.
   *
   * @var string
   */
  public $region;

  /**
   * The entity language.
   *
   * @var string
   */
  public $language = LANGUAGE_NONE;

  /**
   * Merges the entity with a values array.
   *
   * @param array $values
   *   The keyed values to merge.
   *
   * @return Entity
   *   The entity.
   */
  public function merge(array $values) {
    foreach ($values as $key => $value) {
      $this->{$key} = $value;
    }
    return $this;
  }

}
