<?php

namespace Drupal\simple_global_filter;

/**
 * Description of GlobalFilter
 *
 * @author alberto
 */
class GlobalFilter {

  /**
   * Sets the global filter value.
   * @param type $global_filter_id
   * @param type $value
   */
  public function set($global_filter_id, $value) {
    $data = \Drupal::service('session_cache.cache')->get();
    if (!isset($data[$global_filter_id]) || $data[$global_filter_id] != $value) {
      $data[$global_filter_id] = $value;
      \Drupal::service('session_cache.cache')->set($data);
      $cache = &drupal_static('global_filter_get');
      $cache[$global_filter_id] = $value;
    }
  }

  /**
   * Gets the value of the global filter.
   * @param type $global_filter_id
   * @return type
   */
  public function get($global_filter_id) {
    $cache = &drupal_static('global_filter_get');
    if (isset($cache[$global_filter_id])) {
      return $cache[$global_filter_id];
    }

    $value = NULL;
    $data = \Drupal::service('session_cache.cache')->get();
    if (empty($data[$global_filter_id])) {
      // If the user did not select any value, return the default value
      $value = \Drupal::entityTypeManager()->
        getStorage('global_filter')->load($global_filter_id)->getDefaultValue();
    }
    else {
      $value = $data[$global_filter_id];
    }

    $cache[$global_filter_id] = $value;
    return $value;
  }
}
