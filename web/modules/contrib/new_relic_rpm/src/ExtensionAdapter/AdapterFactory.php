<?php

namespace Drupal\new_relic_rpm\ExtensionAdapter;

/**
 * Factory to create a New Relic adapter.
 */
class AdapterFactory {

  /**
   * Returns a new relic adapter.
   *
   * If the extension is not enabled, a null implementation is returned
   * to prevent errors.
   *
   * @return \Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface
   *   The new relic adapter.
   */
  public static function getAdapter() {
    if (extension_loaded('newrelic')) {
      return new ExtensionAdapter();
    }
    else {
      return new NullAdapter();
    }
  }

}
