<?php

namespace Drupal\xhprof\Extension;

/**
 * Implements support for tideways xhprof extension.
 *
 * @see https://github.com/tideways/php-xhprof-extension
 */
class TidewaysXHProfExtension implements ExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public static function isLoaded() {
    return extension_loaded('tideways_xhprof');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return [
      'FLAGS_CPU' => 'TIDEWAYS_XHPROF_FLAGS_CPU',
      'FLAGS_MEMORY' => 'TIDEWAYS_XHPROF_FLAGS_MEMORY',
      'FLAGS_NO_BUILTINS' => 'TIDEWAYS_XHPROF_FLAGS_NO_BUILTINS',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function enable($modifier, $options) {
    tideways_xhprof_enable($modifier);
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    return tideways_xhprof_disable();
  }

}
