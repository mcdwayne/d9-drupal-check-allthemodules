<?php

namespace Drupal\log_entity_operations;

/**
 * Class to find changes between two arrays.
 *
 * Outputs an associative array with added, removed and changed keys.
 *
 * @see https://github.com/charliekassel/array-diff
 *
 * Changes done:
 *   - Using non-strict validation for checking diff - line 63.
 */
class ArrayDiff {

  /**
   * Get the diff recursively in GIT format.
   *
   * @param array $old
   *   Old.
   * @param array $new
   *   New.
   *
   * @return array
   *   Diff.
   */
  public function diff(array $old, array $new): array {
    $added = $this->findAddedKeys($old, $new);
    $removed = $this->findRemovedKeys($old, $new);
    $changed = $this->findChangedKeys($old, $new);

    return compact('added', 'removed', 'changed');
  }

  /**
   * Find the keys added in array.
   *
   * @param array $old
   *   Old.
   * @param array $new
   *   New.
   *
   * @return array
   *   Added diff.
   */
  private function findAddedKeys(array $old, array $new): array {
    return array_filter($new, function ($key) use ($old) {
      return !array_key_exists($key, $old);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Find the keys removed in array.
   *
   * @param array $old
   *   Old.
   * @param array $new
   *   New.
   *
   * @return array
   *   Removed diff.
   */
  private function findRemovedKeys(array $old, array $new): array {
    return array_filter($old, function ($key) use ($new) {
      return !array_key_exists($key, $new);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Find the keys changed in array.
   *
   * @param array $old
   *   Old.
   * @param array $new
   *   New.
   *
   * @return array
   *   Changed diff.
   */
  private function findChangedKeys(array $old, array $new): array {
    $changed = array_filter($new, function ($newItem, $key) use ($old) {
      return array_key_exists($key, $old) && $old[$key] != $newItem;
    }, ARRAY_FILTER_USE_BOTH);

    array_walk($changed, function (&$changedItem, $key) use ($old) {
      if (is_array($changedItem) && !is_null($old[$key])) {
        $changedItem = $this->diff($old[$key], $changedItem);
      }
      else {
        $changedItem = [
          'old' => $old[$key],
          'new' => $changedItem,
        ];
      }
    });

    return $changed;
  }

}
