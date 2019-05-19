<?php

namespace Drupal\xhprof\Extension;

/**
 * Implements support for tideways extension.
 *
 * @see https://tideways.io/profiler/downloads
 */
class TidewaysExtension implements ExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public static function isLoaded() {
    return extension_loaded('tideways');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return [
      'FLAGS_CPU' => 'TIDEWAYS_FLAGS_CPU',
      'FLAGS_MEMORY' => 'TIDEWAYS_FLAGS_MEMORY',
      'FLAGS_NO_BUILTINS' => 'TIDEWAYS_FLAGS_NO_BUILTINS',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function enable($modifier, $options) {
    tideways_enable($modifier, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    return tideways_disable();
  }

}
