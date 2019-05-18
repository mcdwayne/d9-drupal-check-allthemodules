<?php

namespace Drupal\prepared_data\Storage;

use Drupal\prepared_data\Shorthand\ShorthandInterface;

/**
 * Interface for shorthand storages of prepared data keys.
 *
 * By default, this module stores the data
 * in the database. You could write your own storage
 * implementation and exchange the current storage implementation.
 * If you would switch between different storages, you would
 * have to manually delete the database table "prepared_data_short".
 */
interface ShorthandStorageInterface {

  /**
   * Load a shorthand instance by its ID.
   *
   * @param string $id
   *   The instance ID.
   *
   * @return \Drupal\prepared_data\Shorthand\ShorthandInterface|null
   *   The shorthand instance if found.
   */
  public function load($id);

  /**
   * Load a shorthand instance for the given data key.
   *
   * @param string $key
   *   The data key.
   * @param string|string[] $subset_keys
   *   (Optional) Further subset keys.
   *
   * @return \Drupal\prepared_data\Shorthand\ShorthandInterface|null
   *   The shorthand instance if found.
   */
  public function loadFor($key, $subset_keys = []);

  /**
   * Saves the given shorthand instance.
   *
   * @param \Drupal\prepared_data\Shorthand\ShorthandInterface $shorthand
   *   The shorthand instance to save.
   */
  public function save(ShorthandInterface $shorthand);

  /**
   * Delete the record for the given shorthand ID.
   *
   * @param string $id
   *   The ID of the shorthand record to delete.
   */
  public function delete($id);

  /**
   * Deletes any shorthand record which belongs to the given data key.
   *
   * @param string $key
   *   The data key.
   * @param string|string[] $subset_keys
   *   Further subset keys to specify.
   */
  public function deleteFor($key, $subset_keys = []);

  /**
   * Clears all cached shorthand instances.
   */
  public function clearCache();

}
