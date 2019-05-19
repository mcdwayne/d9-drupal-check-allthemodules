<?php

namespace Drupal\yasm_charts\Services;

/**
 * Yasm charts builder interface.
 */
interface YasmChartsBuilderInterface {

  /**
   * Discover charteable content, apply chart settings and build chart array.
   *
   * @param array $build
   *   The rendeable build array.
   * @param array $settings
   *   The charts settings.
   *
   * @return array
   *   The new rendeable array.
   */
  public function discoverCharts($build, $settings);

}
