<?php

namespace Drupal\odoo_api;

/**
 * Cache storage for a form.
 */
trait FormStateCacheTrait {

  /**
   * Form state storage.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Fetches metadata from Odoo, trying to use cached values from form state.
   *
   * @param string $cache_key
   *   Cache key.
   * @param callable $callback
   *   Callback for getting the value.
   * @param bool $use_cache
   *   Whether caching should be used. FALSE to skip cache.
   *
   * @return mixed
   *   Callback return result.
   */
  protected function cacheResponse($cache_key, callable $callback, $use_cache = TRUE) {
    if (!$use_cache) {
      return call_user_func($callback);
    }

    if ($this->formState->get($cache_key) === NULL) {
      $this->formState->set($cache_key, call_user_func($callback));
    }

    return $this->formState->get($cache_key);
  }

}
