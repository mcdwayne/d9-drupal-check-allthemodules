<?php

namespace Drupal\paragraphs_collection;

/**
 * Provides discovery for a YAML grid layout files in specific directories.
 */
interface GridLayoutDiscoveryInterface {

  /**
   * Get a list of library names for the given layout.
   *
   * @param string $layout
   *   The layout name.
   *
   * @return string[]
   *   The library names list.
   */
  public function getLibraries($layout);

  /**
   * Get defined grid layouts.
   *
   * @return array
   *   Array of defined grid layouts.
   */
  public function getGridLayouts();

  /**
   * Gets sorted grid layout titles keyed by their machine names.
   *
   * @return array
   *   Array of availalable layout options in key value pairs, where the key
   *   is the machine name and the value the description.
   */
  public function getLayoutOptions();

  /**
   * Get layout by layout name.
   *
   * @param string $layout
   *   The layout name.
   *
   * @return array
   *   The layout configuration.
   */
  public function getLayout($layout);
}
