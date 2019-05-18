<?php

namespace Drupal\prepared_data\Shorthand;

use Drupal\prepared_data\Storage\ShorthandStorageTrait;

/**
 * Factory class for getting shorthands of prepared data keys.
 */
class ShorthandsFactory {

  use ShorthandStorageTrait;

  /**
   * Get a shorthand instance by the given shorthand ID.
   *
   * @param string $id
   *   The shorthand id to get.
   *
   * @return \Drupal\prepared_data\Shorthand\ShorthandInterface|null
   *   The shorthand instance if found, NULL otherwise.
   */
  public function get($id) {
    return $this->getShorthandStorage()->load($id);
  }

  /**
   * Get a shorthand instance which represents the given data key.
   *
   * @param string $key
   *   The data key to represent.
   * @param string|string[] $subset_keys
   *   The subset keys to represent.
   *
   * @return \Drupal\prepared_data\Shorthand\ShorthandInterface
   *   The shorthand instance.
   */
  public function getFor($key, $subset_keys = []) {
    $storage = $this->getShorthandStorage();
    if (!($shorthand = $storage->loadFor($key, $subset_keys))) {
      $shorthand = new Shorthand($this->newId(), $key, $subset_keys);
      $storage->save($shorthand);
    }
    return $shorthand;
  }

  /**
   * Generates a new shorthand ID.
   *
   * @return string
   *   The generated ID.
   */
  public function newId() {
    return uniqid();
  }

}
