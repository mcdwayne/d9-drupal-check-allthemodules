<?php

namespace Drupal\mason;

/**
 * Defines re-usable services and functions for mason plugins.
 */
interface MasonManagerInterface {

  /**
   * Returns a cacheable renderable array of a single mason instance.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of mason contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS options.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *
   * @return array
   *   The cacheable renderable array of a mason instance, or empty array.
   */
  public function build(array $build = []);

}
